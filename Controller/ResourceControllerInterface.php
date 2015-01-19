<?php

namespace Dunglas\JsonLdApiBundle\Controller;

/**
 * Resource Controller Interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceControllerInterface
{
    /**
     * Sets the Resource associated with this controller.
     *
     * @param string $resourceServiceId
     */
    public function setResourceServiceId($resourceServiceId);
}
