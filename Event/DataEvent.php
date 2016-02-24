<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Event;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * ObjectEvent.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataEvent extends Event
{
    /**
     * @var ResourceInterface
     */
    private $resource;
    /**
     * @var object|array
     */
    private $data;

    /**
     * @param ResourceInterface $resource
     * @param object|array      $data
     */
    public function __construct(ResourceInterface $resource, $data)
    {
        $this->resource = $resource;
        $this->data = $data;
    }

    /**
     * Gets related resource.
     *
     * @return ResourceInterface
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Gets related data.
     *
     * @return object|array
     */
    public function getData()
    {
        return $this->data;
    }
}
