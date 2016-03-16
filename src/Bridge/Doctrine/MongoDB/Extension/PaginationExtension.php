<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ItemMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Query\Builder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Paginator;

/**
 * Applies pagination on the Doctrine query for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class PaginationExtension implements QueryResultExtensionInterface
{
    private $managerRegistry;
    private $requestStack;
    private $itemMetadataFactory;
    private $enabled;
    private $clientEnabled;
    private $clientItemsPerPage;
    private $itemsPerPage;
    private $pageParameterName;
    private $enabledParameterName;
    private $itemsPerPageParameterName;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, ItemMetadataFactoryInterface $itemMetadataFactory, bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $pageParameterName = 'page', string $enabledParameterName = 'pagination', string $itemsPerPageParameterName = 'itemsPerPage')
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->enabled = $enabled;
        $this->clientEnabled = $clientEnabled;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->pageParameterName = $pageParameterName;
        $this->enabledParameterName = $enabledParameterName;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $itemMetadata = $this->itemMetadataFactory->create($resourceClass);
        if (!$this->isPaginationEnabled($request, $itemMetadata, $operationName)) {
            return;
        }

        $itemsPerPage = $itemMetadata->getCollectionOperationAttribute($operationName, 'pagination_items_per_page', $this->itemsPerPage, true);
        if ($itemMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
            $itemsPerPage = (int) $request->query->get($this->itemsPerPageParameterName, $itemsPerPage);
        }

        $queryBuilder
            ->skip(($request->query->get($this->pageParameterName, 1) - 1) * $itemsPerPage)
            ->limit($itemsPerPage)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null) : bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $itemMetadata = $this->itemMetadataFactory->create($resourceClass);

        return $this->isPaginationEnabled($request, $itemMetadata, $operationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(Builder $queryBuilder)
    {
        return new Paginator($queryBuilder->getQuery()->execute());
    }

    private function isPaginationEnabled(Request $request, ItemMetadata $itemMetadata, string $operationName = null) : bool
    {
        $enabled = $itemMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', $this->enabled, true);
        $clientEnabled = $itemMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->clientEnabled, true);

        if ($clientEnabled) {
            $enabled = filter_var($request->query->get($this->enabledParameterName, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    /**
     * Determines whether output walkers should be used.
     *
     * @param Builder $queryBuilder
     *
     * @return bool
     */
    private function useOutputWalkers(Builder $queryBuilder) : bool
    {
        /*
         * "Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L87
         */
//        if (QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder, $this->managerRegistry)) {
//            return true;
//        }

        /*
         * "Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L149
         */
//        if (
//            QueryChecker::hasMaxResults($queryBuilder) &&
//            QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $this->managerRegistry)
//        ) {
//            return true;
//        }

        // Disable output walkers by default (performance)
        return false;
    }
}
