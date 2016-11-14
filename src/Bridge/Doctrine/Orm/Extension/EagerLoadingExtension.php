<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * Eager loads relations.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class EagerLoadingExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceMetadataFactory;
    private $maxJoins;
    private $eagerOnly;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, int $maxJoins = 30, bool $eagerOnly = true)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->maxJoins = $maxJoins;
        $this->eagerOnly = $eagerOnly;
    }

    /**
     * Gets serializer groups once if available, if not it returns the $options array.
     *
     * @param array  $options       represents the operation name so that groups are the one of the specific operation
     * @param string $resourceClass
     * @param string $context       normalization_context or denormalization_context
     *
     * @return string[]
     */
    private function getSerializerGroups(string $resourceClass, array $options, string $context): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (isset($options['collection_operation_name'])) {
            $context = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], $context, null, true);
        } elseif (isset($options['item_operation_name'])) {
            $context = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], $context, null, true);
        } else {
            $context = $resourceMetadata->getAttribute($context);
        }

        if (empty($context['groups'])) {
            return $options;
        }

        return ['serializer_groups' => $context['groups']];
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

        $groups = $this->getSerializerGroups($resourceClass, $options, 'normalization_context');

        $this->joinRelations($queryBuilder, $resourceClass, $groups);
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

        if (isset($context['groups'])) {
            $groups = ['serializer_groups' => $context['groups']];
        } elseif (isset($context['resource_class'])) {
            $groups = $this->getSerializerGroups($context['resource_class'], $options, isset($context['api_denormalize']) ? 'denormalization_context' : 'normalization_context');
        } else {
            $groups = $this->getSerializerGroups($resourceClass, $options, 'normalization_context');
        }

        $this->joinRelations($queryBuilder, $resourceClass, $groups);
    }

    /**
     * Joins relations to eager load.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     * @param array        $propertyMetadataOptions
     * @param string       $originAlias             the current entity alias (first o, then a1, a2 etc.)
     * @param string       $relationAlias           the previous relation alias to keep it unique
     * @param bool         $wasLeftJoin             if the relation containing the new one had a left join, we have to force the new one to left join too
     * @param int          $joinCount               the number of joins
     *
     * @throws RuntimeException when the max number of joins has been reached
     */
    private function joinRelations(QueryBuilder $queryBuilder, string $resourceClass, array $propertyMetadataOptions = [], string $originAlias = 'o', string &$relationAlias = 'a', bool $wasLeftJoin = false, int &$joinCount = 0)
    {
        if ($joinCount > $this->maxJoins) {
            throw new RuntimeException('The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary.');
        }

        $entityManager = $queryBuilder->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata($resourceClass);
        $i = $j = 0;

        foreach ($classMetadata->associationMappings as $association => $mapping) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association, $propertyMetadataOptions);

            if (true === $this->eagerOnly && ClassMetadataInfo::FETCH_EAGER !== $mapping['fetch']) {
                continue;
            }

            if (false === $propertyMetadata->isReadableLink() || false === $propertyMetadata->isReadable()) {
                continue;
            }

            $joinColumns = $mapping['joinColumns'] ?? $mapping['joinTable']['joinColumns'] ?? null;
            if (false !== $wasLeftJoin || !isset($joinColumns[0]['nullable']) || false !== $joinColumns[0]['nullable']) {
                $method = 'leftJoin';
            } else {
                $method = 'innerJoin';
            }

            $associationAlias = $relationAlias.$i++;
            $queryBuilder->{$method}($originAlias.'.'.$association, $associationAlias);
            ++$joinCount;
            $select = [];
            $targetClassMetadata = $entityManager->getClassMetadata($mapping['targetEntity']);

            foreach ($this->propertyNameCollectionFactory->create($mapping['targetEntity']) as $property) {
                $propertyMetadata = $this->propertyMetadataFactory->create($mapping['targetEntity'], $property, $propertyMetadataOptions);

                if (true === $propertyMetadata->isIdentifier()) {
                    $select[] = $property;
                    continue;
                }

                //the field test allows to add methods to a Resource which do not reflect real database fields
                if (true === $targetClassMetadata->hasField($property) && true === $propertyMetadata->isReadable()) {
                    $select[] = $property;
                }
            }

            $queryBuilder->addSelect(sprintf('partial %s.{%s}', $associationAlias, implode(',', $select)));

            $relationAlias .= ++$j;

            $this->joinRelations($queryBuilder, $mapping['targetEntity'], $propertyMetadataOptions, $associationAlias, $relationAlias, $method === 'leftJoin', $joinCount);
        }
    }
}
