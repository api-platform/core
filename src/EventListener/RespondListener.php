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

namespace ApiPlatform\Core\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondListener
{
    const METHOD_TO_CODE = [
        Request::METHOD_POST => Response::HTTP_CREATED,
        Request::METHOD_DELETE => Response::HTTP_NO_CONTENT,
    ];

    /**
     * Creates a Response to send to the client according to the requested format.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->get('_api_respond')) {
            return;
        }

        $event->setResponse(new Response(
            $controllerResult,
            self::METHOD_TO_CODE[$request->getMethod()] ?? Response::HTTP_OK,
            [
                'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
                'Vary' => 'Accept',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
            ]
        ));
    }
}
