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

use ApiPlatform\Doctrine\Odm\Paginator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Applies pagination on the Doctrine aggregation for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PaginationExtension implements AggregationResultCollectionExtensionInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly Pagination $pagination)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        if (!$this->pagination->isEnabled($operation, $context)) {
            return;
        }

        if (($context['graphql_operation_name'] ?? false) && !$this->pagination->isGraphQlEnabled($operation, $context)) {
            return;
        }

        $context = $this->addCountToContext(clone $aggregationBuilder, $context);

        [, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException(\sprintf('The manager for "%s" must be an instance of "%s".', $resourceClass, DocumentManager::class));
        }

        /**
         * @var DocumentRepository
         */
        $repository = $manager->getRepository($resourceClass);

        $facet = $aggregationBuilder->facet();
        $addFields = $aggregationBuilder->addFields();

        // Get the results slice, from $offset to $offset + $limit
        // MongoDB does not support $limit: O, so we return an empty array directly
        if ($limit > 0) {
            $facet->field('results')->pipeline($repository->createAggregationBuilder()->skip($offset)->limit($limit));
        } else {
            $addFields->field('results')->literal([]);
        }

        // Count the total number of items
        $facet->field('count')->pipeline($repository->createAggregationBuilder()->count('count'));

        // Store pagination metadata, read by the Paginator
        // Using __ to avoid field names mapping
        $addFields->field('__api_first_result__')->literal($offset);
        $addFields->field('__api_max_results__')->literal($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
    {
        if ($context['graphql_operation_name'] ?? false) {
            return $this->pagination->isGraphQlEnabled($operation, $context);
        }

        return $this->pagination->isEnabled($operation, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getResult(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array $context = []): iterable
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException(\sprintf('The manager for "%s" must be an instance of "%s".', $resourceClass, DocumentManager::class));
        }

        $attribute = $operation?->getExtraProperties()['doctrine_mongodb'] ?? [];
        $executeOptions = $attribute['execute_options'] ?? [];

        return new Paginator($aggregationBuilder->getAggregation($executeOptions)->getIterator(), $manager->getUnitOfWork(), $resourceClass);
    }

    private function addCountToContext(Builder $aggregationBuilder, array $context): array
    {
        if (!($context['graphql_operation_name'] ?? false)) {
            return $context;
        }

        if (isset($context['filters']['last']) && !isset($context['filters']['before'])) {
            $context['count'] = $aggregationBuilder->count('count')->execute()->toArray()[0]['count'];
        }

        return $context;
    }
}
