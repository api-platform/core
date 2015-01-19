<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\EventListener;

use Dunglas\JsonLdApiBundle\Controller\ResourceControllerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Resource Listener.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceListener
{
    /**
     * Populates the associated Resource.
     *
     * @param FilterControllerEvent $filterControllerEvent
     */
    public function onKernelController(FilterControllerEvent $filterControllerEvent)
    {
        $controller = $filterControllerEvent->getController();

        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof ResourceControllerInterface) {
            $controller[0]->setResourceServiceId($filterControllerEvent->getRequest()->attributes->get('_json_ld_api_resource'));
        }
    }
}
