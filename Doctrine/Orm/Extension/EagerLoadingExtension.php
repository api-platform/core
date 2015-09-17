<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\QueryCollectionExtensionInterface;
use Dunglas\ApiBundle\Doctrine\Orm\QueryItemExtensionInterface;

/**
 * Eager loads relations.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EagerLoadingExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function applyToCollection(ResourceInterface $resource, QueryBuilder $queryBuilder)
    {
        $this->joinRelations($resource, $queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function applyToItem(ResourceInterface $resource, QueryBuilder $queryBuilder, $id)
    {
        $this->joinRelations($resource, $queryBuilder);
    }

    /**
     * Left joins relations to eager load.
     *
     * @param ResourceInterface $resource
     * @param QueryBuilder      $queryBuilder
     */
    private function joinRelations(ResourceInterface $resource, QueryBuilder $queryBuilder)
    {
        $classMetaData = $queryBuilder->getEntityManager()->getClassMetadata($resource->getEntityClass());

        foreach ($classMetaData->getAssociationNames() as $i => $association) {
            $mapping = $classMetaData->associationMappings[$association];
            if (ClassMetadataInfo::FETCH_EAGER === $mapping['fetch']) {
                $queryBuilder->leftJoin('o.'.$association, 'a'.$i);
                $queryBuilder->addSelect('a'.$i);
            }
        }
    }
}
