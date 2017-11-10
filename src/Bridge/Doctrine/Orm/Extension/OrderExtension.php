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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies selected ordering while querying resource collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class OrderExtension implements QueryCollectionExtensionInterface
{
    private $order;
    private $resourceMetadataFactory;

    public function __construct(string $order = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->order = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $classMetaData = $queryBuilder->getEntityManager()->getClassMetadata($resourceClass);
        $identifiers = $classMetaData->getIdentifier();
        if (null !== $this->resourceMetadataFactory) {
            $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
            if (null !== $defaultOrder) {
                foreach ($defaultOrder as $field => $order) {
                    if (is_int($field)) {
                        // Default direction
                        $field = $order;
                        $order = 'ASC';
                    }
                    if (false === ($pos = strpos($field, '.'))) {
                        // Configure default filter with property
                        $field = 'o.'.$field;
                    } else {
                        $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, 'o', substr($field, 0, $pos));
                        $field = sprintf('%s.%s', $alias, substr($field, $pos + 1));
                    }
                    $queryBuilder->addOrderBy($field, $order);
                }

                return;
            }
        }

        if (null !== $this->order) {
            foreach ($identifiers as $identifier) {
                $queryBuilder->addOrderBy('o.'.$identifier, $this->order);
            }
        }
    }
}
