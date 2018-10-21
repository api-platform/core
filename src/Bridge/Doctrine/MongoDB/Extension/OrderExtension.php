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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\PropertyHelperTrait as MongoDbPropertyHelperTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Applies selected ordering while querying resource collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class OrderExtension implements AggregationCollectionExtensionInterface
{
    use PropertyHelperTrait;
    use MongoDbPropertyHelperTrait;

    private $order;
    private $resourceMetadataFactory;

    public function __construct(string $order = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ManagerRegistry $managerRegistry = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->order = $order;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass = null, string $operationName = null, array &$context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $classMetaData = $this->getClassMetadata($resourceClass);
        $identifiers = $classMetaData->getIdentifier();
        if (null !== $this->resourceMetadataFactory) {
            $defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order');
            if (null !== $defaultOrder) {
                foreach ($defaultOrder as $field => $order) {
                    if (\is_int($field)) {
                        // Default direction
                        $field = $order;
                        $order = 'ASC';
                    }

                    if ($this->isPropertyNested($field, $resourceClass)) {
                        [$field] = $this->addLookupsForNestedProperty($field, $aggregationBuilder, $resourceClass);
                    }
                    $aggregationBuilder->sort(
                        $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$field => $order]
                    );
                }

                return;
            }
        }

        if (null !== $this->order) {
            foreach ($identifiers as $identifier) {
                $aggregationBuilder->sort(
                    $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$identifier => $this->order]
                );
            }
        }
    }
}
