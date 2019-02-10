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

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondListener
{
    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Creates a Response to send to the client according to the requested format.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Creates a Response to send to the client according to the requested format.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $controllerResult = $event->getData();
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseForControllerResultEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $controllerResult = $event->getControllerResult();
            $request = $event->getRequest();
        } else {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);
        if ($controllerResult instanceof Response && ($attributes['respond'] ?? false)) {
            if ($event instanceof EventInterface) {
                $event->setContext($event->getContext() + ['response' => $controllerResult]);
            } elseif ($event instanceof GetResponseForControllerResultEvent) {
                $event->setResponse($controllerResult);
            }

            return;
        }
        if ($controllerResult instanceof Response || !($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond', false))) {
            return;
        }

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        if ($request->attributes->has('_api_write_item_iri')) {
            $headers['Content-Location'] = $request->attributes->get('_api_write_item_iri');

            if ($request->isMethod('POST')) {
                $headers['Location'] = $request->attributes->get('_api_write_item_iri');
            }
        }

        $status = null;
        if ($this->resourceMetadataFactory && $attributes) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);

            if ($sunset = $resourceMetadata->getOperationAttribute($attributes, 'sunset', null, true)) {
                $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTime::RFC1123);
            }

            $status = $resourceMetadata->getOperationAttribute($attributes, 'status');
        }

        $response = new Response(
            $controllerResult,
            $status ?? self::METHOD_TO_CODE[$request->getMethod()] ?? Response::HTTP_OK,
            $headers
        );

        if ($event instanceof EventInterface) {
            $event->setContext($event->getContext() + ['response' => $response]);
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            $event->setResponse($response);
        }
    }
}
