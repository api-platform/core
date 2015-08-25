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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Paginator;
use Dunglas\ApiBundle\Doctrine\Orm\QueryResultExtensionInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Util\QueryUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies pagination on the Doctrine query for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class PaginationExtension implements QueryResultExtensionInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param RequestStack    $requestStack
     */
    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack)
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(ResourceInterface $resource, QueryBuilder $queryBuilder)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$this->isPaginationEnabled($resource, $request)) {
            return;
        }

        $itemsPerPage = $this->getItemsPerPage($resource, $request);

        $queryBuilder
            ->setFirstResult(($this->getPage($resource, $request) - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;
    }

    /**
     * @param ResourceInterface $resource
     *
     * @return bool
     */
    public function supportsResult(ResourceInterface $resource)
    {
        return $this->isPaginationEnabled($resource, $this->requestStack->getCurrentRequest());
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder)
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder);

        $doctrineOrmPaginator->setUseOutputWalkers($this->useOutputWalkers($queryBuilder));

        return new Paginator($doctrineOrmPaginator);
    }

    /**
     * Checks if the pagination is enabled or not.
     *
     * @param ResourceInterface $resource
     * @param Request           $request
     *
     * @return bool
     */
    private function isPaginationEnabled(ResourceInterface $resource, Request $request)
    {
        $clientPagination = $request->get($resource->getEnablePaginationParameter());

        if ($resource->isClientAllowedToEnablePagination() && null !== $clientPagination) {
            return (bool) $clientPagination;
        }

        return $resource->isPaginationEnabledByDefault();
    }

    /**
     * Gets the current page.
     *
     * @param ResourceInterface $resource
     * @param Request           $request
     *
     * @return float
     */
    private function getPage(ResourceInterface $resource, Request $request)
    {
        return (float) $request->get($resource->getPageParameter(), 1.);
    }

    /**
     * Gets the number of items per page to display.
     *
     * @param ResourceInterface $resource
     * @param Request           $request
     *
     * @return float
     */
    private function getItemsPerPage(ResourceInterface $resource, Request $request)
    {
        if ($resource->isClientAllowedToChangeItemsPerPage()
            && $itemsPerPage = $request->get($resource->getItemsPerPageParameter())) {
            return (float) $itemsPerPage;
        }

        return $resource->getItemsPerPageByDefault();
    }

    /**
     * Determines whether output walkers should be used.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    private function useOutputWalkers(QueryBuilder $queryBuilder)
    {
        /*
         * "Cannot count query that uses a HAVING clause. Use the output walkers for pagination"
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/CountWalker.php#L50
         */
        if (QueryUtils::hasHavingClause($queryBuilder)) {
            return true;
        }

        /*
         * "Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L87
         */
        if (QueryUtils::hasRootEntityWithForeignKeyIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        /*
         * "Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L149
         */
        if (
            QueryUtils::hasMaxResults($queryBuilder)
            && QueryUtils::hasOrderByOnToManyJoin($queryBuilder, $this->managerRegistry)
        ) {
            return true;
        }

        // Disable output walkers by default (performance)
        return false;
    }
}
