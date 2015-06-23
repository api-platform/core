<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Model;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pagination related features for data providers.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait PaginationTrait
{
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
