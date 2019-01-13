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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Paginator;
use ApiPlatform\Core\DataProvider\Pagination;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Applies pagination on the Doctrine aggregation for resource collection when enabled.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PaginationExtension implements AggregationResultCollectionExtensionInterface
{
    private $managerRegistry;
    private $pagination;

    public function __construct(ManagerRegistry $managerRegistry, Pagination $pagination)
    {
        $this->managerRegistry = $managerRegistry;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (!$this->pagination->isEnabled($resourceClass, $operationName)) {
            return;
        }

        [, $offset, $limit] = $this->pagination->getPagination($resourceClass, $operationName);

        $repository = $this->managerRegistry->getManagerForClass($resourceClass)->getRepository($resourceClass);
        $aggregationBuilder
            ->facet()
            ->field('results')->pipeline(
                $repository->createAggregationBuilder()
                    ->skip($offset)
                    ->limit($limit)
            )
            ->field('count')->pipeline(
                $repository->createAggregationBuilder()
                    ->count('count')
            );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->pagination->isEnabled($resourceClass, $operationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = [])
    {
        return new Paginator($aggregationBuilder->execute(), $this->managerRegistry->getManagerForClass($resourceClass)->getUnitOfWork(), $resourceClass, $aggregationBuilder->getPipeline());
    }
}
