<?php

/*
 * This file is part of the DunglasApiBundle package.
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
 * SaveErrorEvent.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SaveErrorEvent extends Event
{
    /**
     * @var mixed
     */
    private $resource;

    /**
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }
}
