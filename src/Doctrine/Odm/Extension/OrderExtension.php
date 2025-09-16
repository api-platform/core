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

namespace ApiPlatform\Doctrine\Odm\Extension;

use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Doctrine\Odm\PropertyHelperTrait as MongoDbOdmPropertyHelperTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;
use Doctrine\Persistence\ManagerRegistry;

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
    use MongoDbOdmPropertyHelperTrait;
    use PropertyHelperTrait;

    public function __construct(private readonly ?string $order = null, private readonly ?ManagerRegistry $managerRegistry = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        // Do not apply order if already defined on $aggregationBuilder
        if ($this->hasSortStage($aggregationBuilder)) {
            return;
        }

        $classMetaData = $this->getClassMetadata($resourceClass);
        $identifiers = $classMetaData->getIdentifier();
        if (isset($context['operation'])) {
            $defaultOrder = $context['operation']->getOrder() ?? [];
        } else {
            $defaultOrder = $operation?->getOrder();
        }

        if ($defaultOrder) {
            foreach ($defaultOrder as $field => $order) {
                if (\is_int($field)) {
                    // Default direction
                    $field = $order;
                    $order = 'ASC';
                }

                if ($this->isPropertyNested($field, $resourceClass)) {
                    [$field] = $this->addLookupsForNestedProperty($field, $aggregationBuilder, $resourceClass, true);
                }
                $this->addSort(
                    $aggregationBuilder,
                    $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$field => $order]
                );
            }

            return;
        }

        if (null !== $this->order) {
            foreach ($identifiers as $identifier) {
                $this->addSort(
                    $aggregationBuilder,
                    $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$identifier => $this->order]
                );
            }
        }
    }

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    private function addSort(Builder $aggregationBuilder, array $sortFields): void
    {
        $firstStage = $aggregationBuilder->getPipeline(0);
        if ($firstStage instanceof Search) {
            // The $search stage supports "sort" for performance, it's always first if present
            $firstStage->sort($sortFields);
        } else {
            // Append a $sort stage at the end of the pipeline
            $aggregationBuilder->sort($sortFields);
        }
    }

    private function hasSortStage(Builder $aggregationBuilder): bool
    {
        try {
            for ($index = 0; true; $index++) {
                if ($aggregationBuilder->getStage($index) instanceof Sort) {
                    // If at least one stage is sort, then it has sorting
                    return true;
                }
            }
        } catch (\OutOfRangeException) {
            // There is no more stages on the aggregation builder
            return false;
        }
    }
}
