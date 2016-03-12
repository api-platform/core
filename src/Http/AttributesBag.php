<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Http;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class AttributesBag
{
    /**
     * @var string|null
     */
    private $collectionOperationName;

    /**
     * @var string|null
     */
    private $itemOperationName;

    /**
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $resourceClass;

    /**
     * @param string      $resourceClass           Resource FQCN
     * @param string|null $collectionOperationName Example: 'get', 'post', etc.
     * @param string|null $itemOperationName       Example: 'get', 'post', etc.
     * @param string      $format                  Example: 'jsonld'
     */
    public function __construct(
        string $resourceClass,
        string $collectionOperationName = null,
        string $itemOperationName = null,
        string $format
    ) {
        $this->resourceClass = $resourceClass;
        $this->collectionOperationName = $collectionOperationName;
        $this->itemOperationName = $itemOperationName;
        $this->format = $format;
    }

    /**
     * @return string|null Is null if is an item operation
     *
     * @example
     *  'get', 'post', etc.
     */
    public function getCollectionOperationName()
    {
        return $this->collectionOperationName;
    }

    /**
     * @return string|null Is null if is a collection operation
     *
     * @example
     *  'get', 'post', etc.
     */
    public function getItemOperationName()
    {
        return $this->itemOperationName;
    }

    /**
     * @return string
     *
     * @example
     *  'jsonld'
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return string Resource FQCN
     */
    public function getResourceClass()
    {
        return $this->resourceClass;
    }
}
