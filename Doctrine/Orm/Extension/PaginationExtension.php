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

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Paginator;
use Dunglas\ApiBundle\Doctrine\Orm\QueryResultExtensionInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
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
        // Disable output walkers by default (performance)
        $doctrineOrmPaginator->setUseOutputWalkers(false);

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
}
