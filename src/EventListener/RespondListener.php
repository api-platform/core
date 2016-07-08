<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Api\RequestAttributesExtractor;
use ApiPlatform\Core\Exception\RuntimeException;
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
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response) {
            return;
        }

        try {
            RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $event->setResponse(new Response(
            $controllerResult,
            self::METHOD_TO_CODE[$request->getMethod()] ?? Response::HTTP_OK,
            ['Content-Type' => $request->getMimeType($request->getRequestFormat())]
        ));
    }
}
