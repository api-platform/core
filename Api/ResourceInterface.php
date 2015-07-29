<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Api\Filter\FilterInterface;
use Dunglas\ApiBundle\Api\Operation\OperationInterface;

/**
 * An API resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceInterface
{
    /**
     * Gets the associated entity class.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Gets the short name (display name) of the resource.
     *
     * @return string
     */
    public function getShortName();

    /**
     * Get item operations.
     *
     * @return OperationInterface[]
     */
    public function getItemOperations();

    /**
     * Get collection operations.
     *
     * @return OperationInterface[]
     */
    public function getCollectionOperations();

    /**
     * Gets filters available for this resource.
     *
     * @return FilterInterface[]
     */
    public function getFilters();

    /**
     * Gets the normalization context.
     *
     * @return array
     */
    public function getNormalizationContext();

    /**
     * Gets normalization groups.
     *
     * @return string[]|null
     */
    public function getNormalizationGroups();

    /**
     * Gets the denormalization context.
     *
     * @return array
     */
    public function getDenormalizationContext();

    /**
     * Gets denormalization groups.
     *
     * @return string[]|null
     */
    public function getDenormalizationGroups();

    /**
     * Gets validation groups to use.
     *
     * @return string[]|null
     */
    public function getValidationGroups();

    /**
     * Is the pagination enabled for this resource?
     *
     * @return bool
     */
    public function isPaginationEnabledByDefault();

    /**
     * Is the client allowed to enable or disable pagination?
     *
     * @return bool
     */
    public function isClientAllowedToEnablePagination();

    /**
     * Returns the number of items by page.
     *
     * Only applicable if the pagination is enabled.
     *
     * @return float
     */
    public function getItemsPerPageByDefault();

    /**
     * Is the client allowed to set the number of items per page?
     *
     * @return bool
     */
    public function isClientAllowedToChangeItemsPerPage();

    /**
     * Gets the query parameter to use client-side to enabled or disable the pagination.
     *
     * @return string
     */
    public function getEnablePaginationParameter();

    /**
     * Gets the query parameter to use client-side to request the page.
     *
     * @return string
     */
    public function getPageParameter();

    /**
     * Gets the query parameter to use client-side to change the number of items per page.
     *
     * @return string
     */
    public function getItemsPerPageParameter();
}
