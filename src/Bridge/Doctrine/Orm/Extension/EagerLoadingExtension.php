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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\EagerLoadingTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
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
    use EagerLoadingTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $classMetadataFactory;
    private $maxJoins;
    private $serializerContextBuilder;
    private $requestStack;

    /**
     * @TODO move $fetchPartial after $forceEager (@soyuka) in 3.0
     */
    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, int $maxJoins = 30, bool $forceEager = true, RequestStack $requestStack = null, SerializerContextBuilderInterface $serializerContextBuilder = null, bool $fetchPartial = false, ClassMetadataFactoryInterface $classMetadataFactory = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->maxJoins = $maxJoins;
        $this->forceEager = $forceEager;
        $this->fetchPartial = $fetchPartial;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $options = [];

        if (null !== $operationName) {
            $options = ['collection_operation_name' => $operationName];
        }

        $forceEager = $this->shouldOperationForceEager($resourceClass, $options);
        $fetchPartial = $this->shouldOperationFetchPartial($resourceClass, $options);
        $serializerContext = $this->getSerializerContext($resourceClass, 'normalization_context', $options);

        $groups = $this->getSerializerGroups($options, $serializerContext);

        $this->joinRelations($queryBuilder, $queryNameGenerator, $resourceClass, $forceEager, $fetchPartial, $queryBuilder->getRootAliases()[0], $groups, $serializerContext);
    }

    /**
     * {@inheritdoc}
     * The context may contain serialization groups which helps defining joined entities that are readable.
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $options = [];

        if (null !== $operationName) {
            $options = ['item_operation_name' => $operationName];
        }

        $forceEager = $this->shouldOperationForceEager($resourceClass, $options);
        $fetchPartial = $this->shouldOperationFetchPartial($resourceClass, $options);
        $contextType = isset($context['api_denormalize']) ? 'denormalization_context' : 'normalization_context';
        $serializerContext = $this->getSerializerContext($context['resource_class'] ?? $resourceClass, $contextType, $options);

        if (isset($context[AbstractNormalizer::GROUPS])) {
            $groups = ['serializer_groups' => $context[AbstractNormalizer::GROUPS]];
        } else {
            $groups = $this->getSerializerGroups($options, $serializerContext);
        }

        $this->joinRelations($queryBuilder, $queryNameGenerator, $resourceClass, $forceEager, $fetchPartial, $queryBuilder->getRootAliases()[0], $groups, $serializerContext);
    }

    /**
     * Joins relations to eager load.
     *
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param bool                        $forceEager
     * @param string                      $parentAlias
     * @param array                       $propertyMetadataOptions
     * @param array                       $context
     * @param bool                        $wasLeftJoin             if the relation containing the new one had a left join, we have to force the new one to left join too
     * @param int                         $joinCount               the number of joins
     * @param int                         $currentDepth            the current max depth
     *
     * @throws RuntimeException when the max number of joins has been reached
     */
    private function joinRelations(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, bool $forceEager, bool $fetchPartial, string $parentAlias, array $propertyMetadataOptions = [], array $context = [], bool $wasLeftJoin = false, int &$joinCount = 0, int $currentDepth = null)
    {
        if ($joinCount > $this->maxJoins) {
            throw new RuntimeException('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary, or use the "max_depth" option of the Symfony serializer.');
        }

        $currentDepth = $currentDepth > 0 ? $currentDepth - 1 : $currentDepth;
        $entityManager = $queryBuilder->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata($resourceClass);
        $attributesMetadata = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($resourceClass)->getAttributesMetadata() : null;

        foreach ($classMetadata->associationMappings as $association => $mapping) {
            //Don't join if max depth is enabled and the current depth limit is reached
            if (isset($context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH]) && 0 === $currentDepth) {
                continue;
            }

            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association, $propertyMetadataOptions);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                //skip properties not found
                continue;
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                //skip associations that are not resource classes
                continue;
            }

            // We don't want to interfere with doctrine on this association
            if (false === $forceEager && ClassMetadataInfo::FETCH_EAGER !== $mapping['fetch']) {
                continue;
            }

            if ((false === $propertyMetadata->isReadableLink() || false === $propertyMetadata->isReadable()) && false === $propertyMetadata->getAttribute('fetchEager', false)) {
                continue;
            }

            $isNullable = $mapping['joinColumns'][0]['nullable'] ?? true;
            if (false !== $wasLeftJoin || true === $isNullable) {
                $method = 'leftJoin';
            } else {
                $method = 'innerJoin';
            }

            $associationAlias = $queryNameGenerator->generateJoinAlias($association);
            $queryBuilder->{$method}(sprintf('%s.%s', $parentAlias, $association), $associationAlias);
            ++$joinCount;

            if (true === $fetchPartial) {
                try {
                    $this->addSelect($queryBuilder, $mapping['targetEntity'], $associationAlias, $propertyMetadataOptions);
                } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                    continue;
                }
            } else {
                $queryBuilder->addSelect($associationAlias);
            }

            // Avoid recursion
            if ($mapping['targetEntity'] === $resourceClass) {
                $queryBuilder->addSelect($associationAlias);
                continue;
            }

            if (isset($attributesMetadata[$association])) {
                $maxDepth = $attributesMetadata[$association]->getMaxDepth();

                // The current depth is the lowest max depth available in the ancestor tree.
                if (null !== $maxDepth && (null === $currentDepth || $maxDepth < $currentDepth)) {
                    $currentDepth = $maxDepth;
                }
            }

            $this->joinRelations($queryBuilder, $queryNameGenerator, $mapping['targetEntity'], $forceEager, $fetchPartial, $associationAlias, $propertyMetadataOptions, $context, 'leftJoin' === $method, $joinCount, $currentDepth);
        }
    }

    private function addSelect(QueryBuilder $queryBuilder, string $entity, string $associationAlias, array $propertyMetadataOptions)
    {
        $select = [];
        $entityManager = $queryBuilder->getEntityManager();
        $targetClassMetadata = $entityManager->getClassMetadata($entity);
        if ($targetClassMetadata->subClasses) {
            $queryBuilder->addSelect($associationAlias);
        } else {
            foreach ($this->propertyNameCollectionFactory->create($entity) as $property) {
                $propertyMetadata = $this->propertyMetadataFactory->create($entity, $property, $propertyMetadataOptions);

                if (true === $propertyMetadata->isIdentifier()) {
                    $select[] = $property;
                    continue;
                }

                //the field test allows to add methods to a Resource which do not reflect real database fields
                if ($targetClassMetadata->hasField($property) && (true === $propertyMetadata->getAttribute('fetchable') || $propertyMetadata->isReadable())) {
                    $select[] = $property;
                }

                if (array_key_exists($property, $targetClassMetadata->embeddedClasses)) {
                    foreach ($this->propertyNameCollectionFactory->create($targetClassMetadata->embeddedClasses[$property]['class']) as $embeddedProperty) {
                        $propertyMetadata = $this->propertyMetadataFactory->create($entity, $property, $propertyMetadataOptions);
                        $propertyName = "$property.$embeddedProperty";
                        if ($targetClassMetadata->hasField($propertyName) && (true === $propertyMetadata->getAttribute('fetchable') || $propertyMetadata->isReadable())) {
                            $select[] = $propertyName;
                        }
                    }
                }
            }

            $queryBuilder->addSelect(sprintf('partial %s.{%s}', $associationAlias, implode(',', $select)));
        }
    }

    /**
     * Gets serializer context.
     *
     * @param string $resourceClass
     * @param string $contextType   normalization_context or denormalization_context
     * @param array  $options       represents the operation name so that groups are the one of the specific operation
     *
     * @return array
     */
    private function getSerializerContext(string $resourceClass, string $contextType, array $options): array
    {
        $request = null;

        if (null !== $this->requestStack && null !== $this->serializerContextBuilder) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if (null !== $this->serializerContextBuilder && null !== $request) {
            return $this->serializerContextBuilder->createFromRequest($request, 'normalization_context' === $contextType);
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (isset($options['collection_operation_name'])) {
            $context = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], $contextType, null, true);
        } elseif (isset($options['item_operation_name'])) {
            $context = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], $contextType, null, true);
        } else {
            $context = $resourceMetadata->getAttribute($contextType);
        }

        return $context ? $context : [];
    }

    /**
     * Gets serializer groups if available, if not it returns the $options array.
     *
     * @param array $options represents the operation name so that groups are the one of the specific operation
     * @param array $context
     *
     * @return array
     */
    private function getSerializerGroups(array $options, array $context): array
    {
        if (empty($context[AbstractNormalizer::GROUPS])) {
            return $options;
        }

        return ['serializer_groups' => $context[AbstractNormalizer::GROUPS]];
    }
}
