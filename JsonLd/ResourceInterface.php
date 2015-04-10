<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\JsonLd;

use Symfony\Component\Routing\RouteCollection;
use Dunglas\JsonLdApiBundle\Model\DataProviderInterface;

/**
 * Class representing a JSON-LD/Hydra resource.
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
     * Gets the related data provider.
     *
     * @return DataProviderInterface
     */
    public function getDataProvider();

    /**
     * Gets resource collection.
     *
     * @return ResourceCollectionInterface
     */
    public function getResourceCollection();

    /**
     * Gets filters available for this resource.
     *
     * @return array
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

    /**
     * Gets the controller name.
     *
     * @return string
     */
    public function getControllerName();

    /**
     * Gets the route collection for this resource.
     *
     * @return RouteCollection
     */
    public function getRouteCollection();

    /**
     * Gets the route associated with the collection.
     *
     * @return string
     */
    public function getCollectionRouteName();

    /**
     * Gets route associated with an item.
     *
     * @return string
     */
    public function getItemRouteName();

    /**
     * Gets item operations.
     *
     * @return array
     */
    public function getItemOperations();

    /**
     * Get collection operations.
     *
     * @return array
     */
    public function getCollectionOperations();

    /**
     * Gets the short name of the resource pluralized and camel cased.
     *
     * @return string
     */
    public function getBeautifiedName();
}
