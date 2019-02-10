<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

final class SwaggerUiListener
{
    /**
     * Sets SwaggerUiAction as controller if the requested format is HTML.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Sets SwaggerUiAction as controller if the requested format is HTML.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }
        if (
            'html' !== $request->getRequestFormat('') ||
            !($request->attributes->has('_api_resource_class') || $request->attributes->getBoolean('_api_respond', false))
        ) {
            return;
        }

        $request->attributes->set('_controller', 'api_platform.swagger.action.ui');
    }
}
