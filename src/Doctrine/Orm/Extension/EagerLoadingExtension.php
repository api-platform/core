<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Eager loads relations.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class EagerLoadingExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly int $maxJoins = 30, private readonly bool $forceEager = true, private readonly bool $fetchPartial = false, private readonly ?ClassMetadataFactoryInterface $classMetadataFactory = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): void
    {
        $this->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    /**
     * The context may contain serialization groups which helps defining joined entities that are readable.
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    private function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass, ?Operation $operation, array $context): void
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $options = [];

        $forceEager = $operation?->getForceEager() ?? $this->forceEager;
        $fetchPartial = $operation?->getFetchPartial() ?? $this->fetchPartial;

        if (!isset($context['groups']) && !isset($context['attributes'])) {
            $contextType = isset($context['api_denormalize']) ? 'denormalization_context' : 'normalization_context';
            if ($operation) {
                $context += 'denormalization_context' === $contextType ? ($operation->getDenormalizationContext() ?? []) : ($operation->getNormalizationContext() ?? []);
            }
        }

        if (empty($context[AbstractNormalizer::GROUPS]) && !isset($context[AbstractNormalizer::ATTRIBUTES])) {
            return;
        }

        if (!empty($context[AbstractNormalizer::GROUPS])) {
            $options['serializer_groups'] = (array) $context[AbstractNormalizer::GROUPS];
        }

        if ($operation && $normalizationGroups = $operation->getNormalizationContext()['groups'] ?? null) {
            $options['normalization_groups'] = $normalizationGroups;
        }

        if ($operation && $denormalizationGroups = $operation->getDenormalizationContext()['groups'] ?? null) {
            $options['denormalization_groups'] = $denormalizationGroups;
        }

        $this->joinRelations($queryBuilder, $queryNameGenerator, $resourceClass, $forceEager, $fetchPartial, $queryBuilder->getRootAliases()[0], $options, $context);
    }

    /**
     * Joins relations to eager load.
     *
     * @param bool $wasLeftJoin  if the relation containing the new one had a left join, we have to force the new one to left join too
     * @param int  $joinCount    the number of joins
     * @param int  $currentDepth the current max depth
     *
     * @throws RuntimeException when the max number of joins has been reached
     */
    private function joinRelations(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, bool $forceEager, bool $fetchPartial, string $parentAlias, array $options = [], array $normalizationContext = [], bool $wasLeftJoin = false, int &$joinCount = 0, ?int $currentDepth = null, ?string $parentAssociation = null): void
    {
        if ($joinCount > $this->maxJoins) {
            throw new RuntimeException('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the "api_platform.eager_loading.max_joins" configuration key (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using the "enable_max_depth" option of the Symfony serializer (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth).');
        }

        $currentDepth = $currentDepth > 0 ? $currentDepth - 1 : $currentDepth;
        $entityManager = $queryBuilder->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata($resourceClass);
        $attributesMetadata = $this->classMetadataFactory?->getMetadataFor($resourceClass)->getAttributesMetadata();

        foreach ($classMetadata->associationMappings as $association => $mapping) {
            // Don't join if max depth is enabled and the current depth limit is reached
            if (0 === $currentDepth && ($normalizationContext[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ?? false)) {
                continue;
            }

            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association, $options);
            } catch (PropertyNotFoundException) {
                // skip properties not found
                continue;
                // @phpstan-ignore-next-line indeed this can be thrown by the SerializerPropertyMetadataFactory
            } catch (ResourceClassNotFoundException) {
                // skip associations that are not resource classes
                continue;
            }

            if (
                // Always skip extra lazy associations
                ClassMetadata::FETCH_EXTRA_LAZY === $mapping['fetch']
                // We don't want to interfere with doctrine on this association
                || (false === $forceEager && ClassMetadata::FETCH_EAGER !== $mapping['fetch'])
            ) {
                continue;
            }

            // prepare the child context
            $childNormalizationContext = $normalizationContext;
            if (isset($normalizationContext[AbstractNormalizer::ATTRIBUTES])) {
                if ($inAttributes = isset($normalizationContext[AbstractNormalizer::ATTRIBUTES][$association])) {
                    $childNormalizationContext[AbstractNormalizer::ATTRIBUTES] = $normalizationContext[AbstractNormalizer::ATTRIBUTES][$association];
                }
            } else {
                $inAttributes = null;
            }

            $fetchEager = $propertyMetadata->getFetchEager();
            $uriTemplate = $propertyMetadata->getUriTemplate();

            if (false === $fetchEager || null !== $uriTemplate) {
                continue;
            }

            if (true !== $fetchEager && (false === $propertyMetadata->isReadable() || false === $inAttributes)) {
                continue;
            }

            // Avoid joining back to the parent that we just came from, but only on *ToOne relations
            if (
                null !== $parentAssociation
                && isset($mapping['inversedBy'])
                && $mapping['sourceEntity'] === $mapping['targetEntity']
                && $mapping['inversedBy'] === $parentAssociation
                && $mapping['type'] & ClassMetadata::TO_ONE
            ) {
                continue;
            }

            $existingJoin = QueryBuilderHelper::getExistingJoin($queryBuilder, $parentAlias, $association);

            if (null !== $existingJoin) {
                $associationAlias = $existingJoin->getAlias();
                $isLeftJoin = Join::LEFT_JOIN === $existingJoin->getJoinType();
            } else {
                $isNullable = $mapping['joinColumns'][0]['nullable'] ?? true;
                $isLeftJoin = false !== $wasLeftJoin || true === $isNullable;
                $method = $isLeftJoin ? 'leftJoin' : 'innerJoin';

                $associationAlias = $queryNameGenerator->generateJoinAlias($association);
                $queryBuilder->{$method}(sprintf('%s.%s', $parentAlias, $association), $associationAlias);
                ++$joinCount;
            }

            if (true === $fetchPartial) {
                try {
                    $this->addSelect($queryBuilder, $mapping['targetEntity'], $associationAlias, $options);
                } catch (ResourceClassNotFoundException) {
                    continue;
                }
            } else {
                $this->addSelectOnce($queryBuilder, $associationAlias);
            }

            // Avoid recursive joins for self-referencing relations
            if ($mapping['targetEntity'] === $resourceClass) {
                continue;
            }

            // Only join the relation's relations recursively if it's a readableLink
            if (true !== $fetchEager && (true !== $propertyMetadata->isReadableLink())) {
                continue;
            }

            if (isset($attributesMetadata[$association])) {
                $maxDepth = $attributesMetadata[$association]->getMaxDepth();

                // The current depth is the lowest max depth available in the ancestor tree.
                if (null !== $maxDepth && (null === $currentDepth || $maxDepth < $currentDepth)) {
                    $currentDepth = $maxDepth;
                }
            }

            $this->joinRelations($queryBuilder, $queryNameGenerator, $mapping['targetEntity'], $forceEager, $fetchPartial, $associationAlias, $options, $childNormalizationContext, $isLeftJoin, $joinCount, $currentDepth, $association);
        }
    }

    private function addSelect(QueryBuilder $queryBuilder, string $entity, string $associationAlias, array $propertyMetadataOptions): void
    {
        $select = [];
        $entityManager = $queryBuilder->getEntityManager();
        $targetClassMetadata = $entityManager->getClassMetadata($entity);
        if (!empty($targetClassMetadata->subClasses)) {
            $this->addSelectOnce($queryBuilder, $associationAlias);

            return;
        }

        foreach ($this->propertyNameCollectionFactory->create($entity) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($entity, $property, $propertyMetadataOptions);

            if (true === $propertyMetadata->isIdentifier()) {
                $select[] = $property;
                continue;
            }

            // If it's an embedded property see below
            if (!\array_key_exists($property, $targetClassMetadata->embeddedClasses)) {
                $isFetchable = $propertyMetadata->isFetchable();
                // the field test allows to add methods to a Resource which do not reflect real database fields
                if ($targetClassMetadata->hasField($property) && (true === $isFetchable || $propertyMetadata->isReadable())) {
                    $select[] = $property;
                }

                continue;
            }

            // It's an embedded property, select relevant subfields
            foreach ($this->propertyNameCollectionFactory->create($targetClassMetadata->embeddedClasses[$property]['class']) as $embeddedProperty) {
                $isFetchable = $propertyMetadata->isFetchable();
                $propertyMetadata = $this->propertyMetadataFactory->create($entity, $property, $propertyMetadataOptions);
                $propertyName = "$property.$embeddedProperty";
                if ($targetClassMetadata->hasField($propertyName) && (true === $isFetchable || $propertyMetadata->isReadable())) {
                    $select[] = $propertyName;
                }
            }
        }

        $queryBuilder->addSelect(sprintf('partial %s.{%s}', $associationAlias, implode(',', $select)));
    }

    private function addSelectOnce(QueryBuilder $queryBuilder, string $alias): void
    {
        $existingSelects = array_reduce($queryBuilder->getDQLPart('select') ?? [], fn ($existing, $dqlSelect) => ($dqlSelect instanceof Select) ? array_merge($existing, $dqlSelect->getParts()) : $existing, []);

        if (!\in_array($alias, $existingSelects, true)) {
            $queryBuilder->addSelect($alias);
        }
    }
}
