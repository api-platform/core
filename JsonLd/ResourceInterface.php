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
     * Initializes the resource collection.
     *
     * @param ResourceCollectionInterface $resourceCollection
     */
    public function initResourceCollection(ResourceCollectionInterface $resourceCollection);

    /**
     * Gets resource collection.
     *
     * @return ResourceCollectionInterface
     */
    public function getResourceCollection();

    /**
     * Initializes filters.
     *
     * @param array $filters
     */
    public function initFilters(array $filters);

    /**
     * Gets filters available for this resource.
     *
     * @return array
     */
    public function getFilters();

    /**
     * Initializes order filters.
     *
     * @param array $filters
     */
    public function initOrder(array $orders);

    /**
     * Gets order filters available for this resource.
     *
     * @return array
     */
    public function getOrder();

    /**
     * Initializes normalization context.
     *
     * @param array $normalizationContext
     */
    public function initNormalizationContext(array $normalizationContext);

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
     * Initializes denormalization context.
     *
     * @param array $denormalizationContext
     */
    public function initDenormalizationContext(array $denormalizationContext);

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
     * Initializes validation groups.
     *
     * @param array $validationGroups
     */
    public function initValidationGroups(array $validationGroups);

    /**
     * Gets validation groups to use.
     *
     * @return string[]|null
     */
    public function getValidationGroups();

    /**
     * Initializes short name.
     *
     * @param string $shortName
     */
    public function initShortName($shortName);

    /**
     * Gets the short name (display name) of the resource.
     *
     * @return string
     */
    public function getShortName();

    /**
     * Initializes controller name.
     *
     * @param string $controllerName
     */
    public function initControllerName($controllerName);

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
     * Initializes item operations.
     *
     * @param array $itemOperations
     */
    public function initItemOperations(array $itemOperations);

    /**
     * Gets item operations.
     *
     * @return array
     */
    public function getItemOperations();

    /**
     * Initializes collection operations.
     *
     * @param array $collectionOperations
     */
    public function initCollectionOperations(array $collectionOperations);

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
