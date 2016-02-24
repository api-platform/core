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
use Dunglas\ApiBundle\Exception\InvalidArgumentException;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Resource implements RoutedResourceInterface
{
    /**
     * @var string
     */
    private $entityClass;
    /**
     * @var OperationInterface[]
     */
    private $itemOperations;
    /**
     * @var OperationInterface[]
     */
    private $collectionOperations;
    /**
     * @var FilterInterface[]
     */
    private $filters = [];
    /**
     * @var array
     */
    private $normalizationContext = [];
    /**
     * @var array
     */
    private $denormalizationContext = [];
    /**
     * @var string[]
     */
    private $validationGroups;
    /**
     * @var string|null
     */
    private $shortName;
    /**
     * @var string|null
     */
    private $itemRouteName;
    /**
     * @var string|null
     */
    private $collectionRouteName;

    /**
     * @param string $entityClass
     *
     * @throws InvalidArgumentException
     */
    public function __construct($entityClass)
    {
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException(sprintf('The class "%s" does not exist.', $entityClass));
        }

        $this->entityClass = $entityClass;
        $this->shortName = substr($this->entityClass, strrpos($this->entityClass, '\\') + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Initializes collection operations.
     *
     * @param OperationInterface[] $collectionOperations
     */
    public function initCollectionOperations(array $collectionOperations)
    {
        $this->collectionOperations = $collectionOperations;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperations()
    {
        return $this->collectionOperations;
    }

    /**
     * Initializes item operations.
     *
     * @param OperationInterface[] $itemOperations
     */
    public function initItemOperations(array $itemOperations)
    {
        $this->itemOperations = $itemOperations;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperations()
    {
        return $this->itemOperations;
    }

    /**
     * Initializes filters.
     *
     * @param array $filters
     */
    public function initFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Initializes normalization context.
     *
     * @param array $normalizationContext
     */
    public function initNormalizationContext(array $normalizationContext)
    {
        $this->normalizationContext = $normalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizationContext()
    {
        if (!isset($this->normalizationContext['resource'])) {
            $this->normalizationContext['resource'] = $this;
        }

        return $this->normalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizationGroups()
    {
        return isset($this->normalizationContext['groups']) ? $this->normalizationContext['groups'] : null;
    }

    /**
     * Initializes denormalization context.
     *
     * @param array $denormalizationContext
     */
    public function initDenormalizationContext(array $denormalizationContext)
    {
        $this->denormalizationContext = $denormalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getDenormalizationContext()
    {
        if (!isset($this->denormalizationContext['resource'])) {
            $this->denormalizationContext['resource'] = $this;
        }

        return $this->denormalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getDenormalizationGroups()
    {
        return isset($this->denormalizationContext['groups']) ? $this->denormalizationContext['groups'] : null;
    }

    /**
     * Initializes validation groups.
     *
     * @param array $validationGroups
     */
    public function initValidationGroups(array $validationGroups)
    {
        $this->validationGroups = $validationGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationGroups()
    {
        return $this->validationGroups;
    }

    /**
     * Initializes short name.
     *
     * @param string $shortName
     */
    public function initShortName($shortName)
    {
        $this->shortName = $shortName;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemRouteName()
    {
        return $this->itemRouteName;
    }

    /**
     * @param string|null $itemRouteName
     */
    public function setItemRouteName($itemRouteName)
    {
        $this->itemRouteName = $itemRouteName;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionRouteName()
    {
        return $this->collectionRouteName;
    }

    /**
     * @param string|null $collectionRouteName
     */
    public function setCollectionRouteName($collectionRouteName)
    {
        $this->collectionRouteName = $collectionRouteName;
    }
}
