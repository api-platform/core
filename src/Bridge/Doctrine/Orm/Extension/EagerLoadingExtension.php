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
 * @author Antoine Bluchet <soyuka@gmail.com>
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
        if (null !== $operationName) {
            $propertyMetadataOptions = ['collection_operation_name' => $operationName];
        }

        $this->joinRelations($queryBuilder, $resourceClass, $propertyMetadataOptions ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null)
    {
        if (null !== $operationName) {
            $propertyMetadataOptions = ['item_operation_name' => $operationName];
        }

        $this->joinRelations($queryBuilder, $resourceClass, $propertyMetadataOptions ?? []);
    }

    /**
     * Left joins relations to eager load.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     * @param array        $propertyMetadataOptions
     * @param string       $originAlias             the current entity alias (first o, then a1, a2 etc.)
     * @param string       $relationAlias           the previous relation alias to keep it unique
     * @param bool         $wasLeftJoin             if the relation containing the new one had a left join, we have to force the new one to left join too
     */
    private function joinRelations(QueryBuilder $queryBuilder, string $resourceClass, array $propertyMetadataOptions = [], string $originAlias = 'o', string &$relationAlias = 'a', bool $wasLeftJoin = false)
    {
        $entityManager = $queryBuilder->getEntityManager();
        $classMetadata = $entityManager->getClassMetadata($resourceClass);
        $j = 0;

        foreach ($classMetadata->getAssociationNames() as $i => $association) {
            $mapping = $classMetadata->associationMappings[$association];
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $association, $propertyMetadataOptions);

            if (ClassMetadataInfo::FETCH_EAGER !== $mapping['fetch'] || false === $propertyMetadata->isReadableLink()) {
                continue;
            }

            if (false === $wasLeftJoin) {
                $joinColumns = $mapping['joinColumns'] ?? $mapping['joinTable']['joinColumns'] ?? null;

                if (null === $joinColumns) {
                    $method = 'leftJoin';
                } else {
                    $method = false === $joinColumns[0]['nullable'] ? 'innerJoin' : 'leftJoin';
                }
            } else {
                $method = 'leftJoin';
            }

            $associationAlias = $relationAlias.$i;
            $queryBuilder->{$method}($originAlias.'.'.$association, $associationAlias);
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

            $relationAlias = $relationAlias.++$j;

            $this->joinRelations($queryBuilder, $mapping['targetEntity'], $propertyMetadataOptions, $associationAlias, $relationAlias, $method === 'leftJoin');
        }
    }
}
