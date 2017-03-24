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

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

final class SwaggerUiListener
{
    /**
     * Sets SwaggerUiAction as controller if the requested format is HTML.
     *
     * @param $event GetResponseEvent
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (
            'html' !== $request->getRequestFormat(null) ||
            (!$request->attributes->has('_api_resource_class') && !$request->attributes->has('_api_respond'))
        ) {
            return;
        }

        $request->attributes->set('_controller', 'api_platform.swagger.action.ui');
    }
}
