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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondListener
{
    use OperationRequestInitiatorTrait;

    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    public function __construct(
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
        private readonly IriConverterInterface|LegacyIriConverterInterface|null $iriConverter = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Creates a Response to send to the client according to the requested format.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();
        $operation = $this->initializeOperation($request);

        if ('api_platform.symfony.main_controller' === $operation?->getController() || $request->attributes->get('_api_platform_disable_listeners')) {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if ($controllerResult instanceof Response && ($attributes['respond'] ?? false)) {
            $event->setResponse($controllerResult);

            return;
        }

        if ($controllerResult instanceof Response || !($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond'))) {
            return;
        }

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        $status = $operation?->getStatus();

        if ($sunset = $operation?->getSunset()) {
            $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTime::RFC1123);
        }

        if ($acceptPatch = $operation?->getAcceptPatch()) {
            $headers['Accept-Patch'] = $acceptPatch;
        }

        $method = $request->getMethod();
        if (
            $this->iriConverter
            && $operation
            && ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
            && 301 === $operation->getStatus()
        ) {
            $status = 301;
            $headers['Location'] = $this->iriConverter->getIriFromResource($request->attributes->get('data'), UrlGeneratorInterface::ABS_PATH, $operation);
        } elseif ('PUT' === $method && !($attributes['previous_data'] ?? null) && null === $status && ($operation instanceof Put && ($operation->getAllowCreate() ?? false))) {
            $status = Response::HTTP_CREATED;
        }

        $status ??= self::METHOD_TO_CODE[$request->getMethod()] ?? Response::HTTP_OK;

        if ($request->attributes->has('_api_write_item_iri')) {
            $headers['Content-Location'] = $request->attributes->get('_api_write_item_iri');

            if ((Response::HTTP_CREATED === $status || (300 <= $status && $status < 400)) && 'POST' === $method) {
                $headers['Location'] = $request->attributes->get('_api_write_item_iri');
            }
        }

        if (($exception = $request->attributes->get('data')) instanceof HttpExceptionInterface) {
            $headers = array_merge($headers, $exception->getHeaders());
        }

        $event->setResponse(new Response(
            $controllerResult,
            $status,
            $headers
        ));
    }
}
