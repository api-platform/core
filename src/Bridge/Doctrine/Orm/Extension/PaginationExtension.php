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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\Factory\PaginationHelperFactory;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies pagination on the Doctrine query for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PaginationExtension implements QueryResultCollectionExtensionInterface
{
    private $managerRegistry;
    private $requestStack;
    private $resourceMetadataFactory;
    private $paginationHelperFactory;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory, bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $pageParameterName = 'page', string $enabledParameterName = 'pagination', string $itemsPerPageParameterName = 'itemsPerPage', int $maximumItemPerPage = null, PaginationHelperFactory $paginationHelperFactory = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (!$paginationHelperFactory) {
            $paginationHelperFactory = new PaginationHelperFactory($requestStack, $resourceMetadataFactory, $enabled, $clientEnabled, $clientItemsPerPage, $itemsPerPage, $pageParameterName, $enabledParameterName, $itemsPerPageParameterName, $maximumItemPerPage);
        }
        $this->paginationHelperFactory = $paginationHelperFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $paginationHelper = $this->paginationHelperFactory->create($resourceClass, $operationName);
        if (!$paginationHelper->isPaginationEnabled()) {
            return;
        }

        $itemsPerPage = $paginationHelper->getItemsPerPage();

        $queryBuilder
            ->setFirstResult(($paginationHelper->getPage() - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        return $this->paginationHelperFactory->create($resourceClass, $operationName)->isPaginationEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder)
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder, $this->useFetchJoinCollection($queryBuilder));
        $doctrineOrmPaginator->setUseOutputWalkers($this->useOutputWalkers($queryBuilder));

        return new Paginator($doctrineOrmPaginator);
    }

    /**
     * Determines whether the Paginator should fetch join collections, if the root entity uses composite identifiers it should not.
     *
     * @see https://github.com/doctrine/doctrine2/issues/2910
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    private function useFetchJoinCollection(QueryBuilder $queryBuilder): bool
    {
        return !QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry);
    }

    /**
     * Determines whether output walkers should be used.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    private function useOutputWalkers(QueryBuilder $queryBuilder): bool
    {
        /*
         * "Cannot count query that uses a HAVING clause. Use the output walkers for pagination"
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/CountWalker.php#L50
         */
        if (QueryChecker::hasHavingClause($queryBuilder)) {
            return true;
        }

        /*
         * "Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L87
         */
        if (QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        /*
         * "Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L149
         */
        if (
            QueryChecker::hasMaxResults($queryBuilder) &&
            QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $this->managerRegistry)
        ) {
            return true;
        }

        /*
         * When using composite identifiers pagination will need Output walkers
         */
        if (QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        // Disable output walkers by default (performance)
        return false;
    }
}
