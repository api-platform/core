<?php

namespace Dunglas\ApiBundle\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Paginator;
use Dunglas\ApiBundle\Doctrine\Orm\QueryResultExtension;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;

class PaginationExtension implements QueryResultExtension
{
    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, Request $request, QueryBuilder $queryBuilder)
    {
        if ($paginationEnabled = $this->isPaginationEnabled($resource, $request)) {
            $itemsPerPage = $this->getItemsPerPage($resource, $request);

            $queryBuilder
                ->setFirstResult(($this->getPage($resource, $request) - 1) * $itemsPerPage)
                ->setMaxResults($itemsPerPage)
            ;
        }
    }

    /**
     * @param ResourceInterface $resource
     * @param Request           $request
     *
     * @return bool
     */
    public function supportsResult(ResourceInterface $resource, Request $request)
    {
        return $this->isPaginationEnabled($resource, $request);
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
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder)
    {
        return new Paginator(new DoctrineOrmPaginator($queryBuilder));
    }
}
