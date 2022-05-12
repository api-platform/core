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

use ApiPlatform\Doctrine\Orm\Util\EagerLoadingTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyMetadata;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
final class EagerLoadingExtension implements ContextAwareQueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    use EagerLoadingTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $classMetadataFactory;
    private $maxJoins;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, int $maxJoins = 30, bool $forceEager = true, bool $fetchPartial = false, ClassMetadataFactoryInterface $classMetadataFactory = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->maxJoins = $maxJoins;
        $this->forceEager = $forceEager;
        $this->fetchPartial = $fetchPartial;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        $this->apply(true, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
    }

    /**
     * {@inheritdoc}
     *
     * The context may contain serialization groups which helps defining joined entities that are readable.
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->apply(false, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
    }

    private function apply(bool $collection, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass, ?string $operationName, array $context)
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $options = [];
        if (null !== $operationName) {
            // TODO remove in 3.0
            $options[($collection ? 'collection' : 'item').'_operation_name'] = $operationName;
        }

        $operation = null;
        $forceEager = $this->forceEager;
        $fetchPartial = $this->fetchPartial;
        $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
        try {
            $operation = $resourceMetadataCollection->getOperation($operationName);
            $forceEager = $operation->getForceEager() ?? $this->forceEager;
            $fetchPartial = $operation->getFetchPartial() ?? $this->fetchPartial;
        } catch (OperationNotFoundException $e) {
            // In some cases the operation may not exist
        }

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
    private function joinRelations(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, bool $forceEager, bool $fetchPartial, string $parentAlias, array $options = [], array $normalizationContext = [], bool $wasLeftJoin = false, int &$joinCount = 0, int $currentDepth = null, string $parentAssociation = null)
    {
        if ($joinCount > $this->maxJoins) {
            throw new RuntimeException('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary with the "api_platform.eager_loading.max_joins" configuration key (https://api-platform.com/docs/core/performance/#eager-loading), or limit the maximum serialization depth using the "enable_max_depth" option of the Symfony serializer (https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth).');
        }

        $currentDepth = $currentDepth > 0 ? $currentDepth - 1 : $currentDepth;
        $entityManager = $queryBuilder->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata($resourceClass);
        $attributesMetadata = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($resourceClass)->getAttributesMetadata() : null;

        foreach ($classMetadata->associationMappings as $association => $mapping) {
            // Don't join if max depth is enabled and the current depth limit is reached
            if (0 === $currentDepth && ($normalizationContext[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ?? false)) {
                continue;
            }

            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // skip properties not found
                continue;
                // @phpstan-ignore-next-line indeed this can be thrown by the SerializerPropertyMetadataFactory
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                // skip associations that are not resource classes
                continue;
            }

            if (
                // Always skip extra lazy associations
                ClassMetadataInfo::FETCH_EXTRA_LAZY === $mapping['fetch'] ||
                // We don't want to interfere with doctrine on this association
                (false === $forceEager && ClassMetadataInfo::FETCH_EAGER !== $mapping['fetch'])
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

            if (false === $fetchEager) {
                continue;
            }

            if (true !== $fetchEager && (false === $propertyMetadata->isReadable() || false === $inAttributes)) {
                continue;
            }

            // Avoid joining back to the parent that we just came from, but only on *ToOne relations
            if (
                null !== $parentAssociation &&
                isset($mapping['inversedBy']) &&
                $mapping['inversedBy'] === $parentAssociation &&
                $mapping['type'] & ClassMetadata::TO_ONE
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
                } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
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

    private function addSelect(QueryBuilder $queryBuilder, string $entity, string $associationAlias, array $propertyMetadataOptions)
    {
        $select = [];
        $entityManager = $queryBuilder->getEntityManager();
        $targetClassMetadata = $entityManager->getClassMetadata($entity);
        if (!empty($targetClassMetadata->subClasses)) {
            $this->addSelectOnce($queryBuilder, $associationAlias);

            return;
        }

        foreach ($this->propertyNameCollectionFactory->create($entity) as $property) {
            /** @var ApiProperty|PropertyMetadata */
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

    private function addSelectOnce(QueryBuilder $queryBuilder, string $alias)
    {
        $existingSelects = array_reduce($queryBuilder->getDQLPart('select') ?? [], function ($existing, $dqlSelect) {
            return ($dqlSelect instanceof Select) ? array_merge($existing, $dqlSelect->getParts()) : $existing;
        }, []);

        if (!\in_array($alias, $existingSelects, true)) {
            $queryBuilder->addSelect($alias);
        }
    }

    /**
     * Gets the serializer context.
     *
     * @param string $contextType normalization_context or denormalization_context
     * @param array  $options     represents the operation name so that groups are the one of the specific operation
     */
    private function getNormalizationContext(string $resourceClass, string $contextType, array $options): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (isset($options['collection_operation_name'])) {
            $context = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], $contextType, null, true);
        } elseif (isset($options['item_operation_name'])) {
            $context = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], $contextType, null, true);
        } else {
            $context = $resourceMetadata->getAttribute($contextType);
        }

        return $context ?? [];
    }
}
