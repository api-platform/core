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
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * Eager loads relations.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EagerLoadingExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $this->joinRelations($queryBuilder, $resourceClass);
    }

    /**
     * {@inheritdoc}
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null)
    {
        $this->joinRelations($queryBuilder, $resourceClass);
    }

    public function getMetadataProperties(string $resourceClass) : array
    {
        $properties = [];

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $properties[$property] = $this->propertyMetadataFactory->create($resourceClass, $property);
        }

        return $properties;
    }

    /**
     * Left joins relations to eager load.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     */
    private function joinRelations(QueryBuilder $queryBuilder, string $resourceClass, string $originAlias = 'o', string &$relationAlias = 'a')
    {
        $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($resourceClass);
        $j = 0;

        foreach ($classMetadata->getAssociationNames() as $i => $association) {
            $mapping = $classMetadata->associationMappings[$association];
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association);

            if (ClassMetadataInfo::FETCH_EAGER !== $mapping['fetch'] || false === $propertyMetadata->isReadableLink()) {
                continue;
            }

            $joinColumns = $mapping['joinColumns'] ?? $mapping['joinTable']['joinColumns'] ?? null;

            if (null === $joinColumns) {
                $method = 'leftJoin';
            } else {
                $method = false === $joinColumns[0]['nullable'] ? 'innerJoin' : 'leftJoin';
            }

            $associationAlias = $relationAlias.$i;
            $queryBuilder->{$method}($originAlias.'.'.$association, $associationAlias);
            $select = [];
            $targetClassMetadata = $queryBuilder->getEntityManager()->getClassMetadata($mapping['targetEntity']);

            foreach ($this->getMetadataProperties($mapping['targetEntity']) as $property => $propertyMetadata) {
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

            $relationAlias = $relationAlias.++$j;
            $this->joinRelations($queryBuilder, $mapping['targetEntity'], $associationAlias, $relationAlias);
        }
    }
}
