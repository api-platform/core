<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Event;

use Dunglas\JsonLdApiBundle\Resource;
use Symfony\Component\EventDispatcher\Event;

/**
 * ObjectEvent.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ObjectEvent extends Event
{
    /**
     * @var Resource
     */
    private $resource;
    /**
     * @var object
     */
    private $object;

    /**
     * @param object $object
     */
    public function __construct(Resource $resource, $object)
    {
        $this->resource = $resource;
        $this->object = $object;
    }

    /**
     * Gets related resource.
     *
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Gets related object.
     *
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }
}
