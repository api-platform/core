<?php

/*
 * This file is part of the API Platform project.
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
 * Class representing an API resource.
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
     * Gets the short name (display name) of the resource.
     *
     * @return string
     */
    public function getShortName();
}
