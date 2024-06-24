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

namespace ApiPlatform\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\AbstractPaginator;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryChecker;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Doctrine\Persistence\ManagerRegistry;

// Help opcache.preload discover always-needed symbols
class_exists(AbstractPaginator::class);

/**
 * Applies pagination on the Doctrine query for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PaginationExtension implements QueryResultCollectionExtensionInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly ?Pagination $pagination)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $pagination = $this->getPagination($queryBuilder, $operation, $context)) {
            return;
        }

        [$offset, $limit] = $pagination;

        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);
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
     */
    public function getResult(QueryBuilder $queryBuilder, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): iterable
    {
        $query = $queryBuilder->getQuery();

        // Only one alias, without joins, disable the DISTINCT on the COUNT
        if (1 === \count($queryBuilder->getAllAliases())) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $doctrineOrmPaginator = new DoctrineOrmPaginator($query, $this->shouldDoctrinePaginatorFetchJoinCollection($queryBuilder, $operation, $context));
        $doctrineOrmPaginator->setUseOutputWalkers($this->shouldDoctrinePaginatorUseOutputWalkers($queryBuilder, $operation, $context));

        $isPartialEnabled = $this->pagination->isPartialEnabled($operation, $context);

        if ($isPartialEnabled) {
            return new class($doctrineOrmPaginator) extends AbstractPaginator {
            };
        }

        return new Paginator($doctrineOrmPaginator);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getPagination(QueryBuilder $queryBuilder, ?Operation $operation, array $context): ?array
    {
        $enabled = isset($context['graphql_operation_name']) ? $this->pagination->isGraphQlEnabled($operation, $context) : $this->pagination->isEnabled($operation, $context);

        if (!$enabled) {
            return null;
        }

        $context = $this->addCountToContext($queryBuilder, $context);

        return \array_slice($this->pagination->getPagination($operation, $context), 1);
    }

    private function addCountToContext(QueryBuilder $queryBuilder, array $context): array
    {
        if (!($context['graphql_operation_name'] ?? false)) {
            return $context;
        }

        if (isset($context['filters']['last']) && !isset($context['filters']['before'])) {
            $context['count'] = (new DoctrineOrmPaginator($queryBuilder))->count();
        }

        return $context;
    }

    /**
     * Determines the value of the $fetchJoinCollection argument passed to the Doctrine ORM Paginator.
     */
    private function shouldDoctrinePaginatorFetchJoinCollection(QueryBuilder $queryBuilder, ?Operation $operation = null, array $context = []): bool
    {
        $fetchJoinCollection = $operation?->getPaginationFetchJoinCollection();

        if (isset($context['operation_name']) && isset($fetchJoinCollection)) {
            return $fetchJoinCollection;
        }

        if (isset($context['graphql_operation_name']) && isset($fetchJoinCollection)) {
            return $fetchJoinCollection;
        }

        /*
         * "Cannot count query which selects two FROM components, cannot make distinction"
         *
         * @see https://github.com/doctrine/orm/blob/v2.6.3/lib/Doctrine/ORM/Tools/Pagination/WhereInWalker.php#L81
         * @see https://github.com/doctrine/doctrine2/issues/2910
         */
        if (QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry)) {
            return false;
        }

        if (QueryChecker::hasJoinedToManyAssociation($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        // disable $fetchJoinCollection by default (performance)
        return false;
    }

    /**
     * Determines whether the Doctrine ORM Paginator should use output walkers.
     */
    private function shouldDoctrinePaginatorUseOutputWalkers(QueryBuilder $queryBuilder, ?Operation $operation = null, array $context = []): bool
    {
        $useOutputWalkers = $operation?->getPaginationUseOutputWalkers();

        if (isset($context['operation_name']) && isset($useOutputWalkers)) {
            return $useOutputWalkers;
        }

        if (isset($context['graphql_operation_name']) && isset($useOutputWalkers)) {
            return $useOutputWalkers;
        }

        /*
         * "Cannot count query that uses a HAVING clause. Use the output walkers for pagination"
         *
         * @see https://github.com/doctrine/orm/blob/v2.6.3/lib/Doctrine/ORM/Tools/Pagination/CountWalker.php#L56
         */
        if (QueryChecker::hasHavingClause($queryBuilder)) {
            return true;
        }

        /*
         * "Cannot count query which selects two FROM components, cannot make distinction"
         *
         * @see https://github.com/doctrine/orm/blob/v2.6.3/lib/Doctrine/ORM/Tools/Pagination/CountWalker.php#L64
         */
        if (QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        /*
         * "Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator."
         *
         * @see https://github.com/doctrine/orm/blob/v2.6.3/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L77
         */
        if (QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        /*
         * "Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers."
         *
         * @see https://github.com/doctrine/orm/blob/v2.6.3/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L150
         */
        if (QueryChecker::hasMaxResults($queryBuilder) && QueryChecker::hasOrderByOnFetchJoinedToManyAssociation($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        // Disable output walkers by default (performance)
        return false;
    }
}
