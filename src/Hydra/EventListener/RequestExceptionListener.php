<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;

/**
 * Handle requests errors.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RequestExceptionListener extends ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // Normalize exceptions with hydra errors only for resources
        if (!$event->getRequest()->attributes->has('_resource_class')) {
            return;
        }

        parent::onKernelException($event);
    }
}
