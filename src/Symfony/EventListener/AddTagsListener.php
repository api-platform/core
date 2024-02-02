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

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Sets the list of resources' IRIs included in this response in the configured cache tag HTTP header and/or "xkey" HTTP headers.
 *
 * By default the "Cache-Tags" HTTP header is used because it is supported by CloudFlare.
 *
 * @see https://developers.cloudflare.com/cache/how-to/purge-cache#add-cache-tag-http-response-headers
 *
 * The "xkey" is used because it is supported by Varnish.
 * @see https://docs.varnish-software.com/varnish-cache-plus/vmods/ykey/
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddTagsListener
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, private readonly ?PurgerInterface $purger = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Adds the configured HTTP cache tag and "xkey" headers.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);
        $response = $event->getResponse();

        if (
            !$request->isMethodCacheable()
            || !$response->isCacheable()
            || (!$attributes = RequestAttributesExtractor::extractAttributes($request))
            || $request->attributes->get('_api_platform_disable_listeners')
        ) {
            return;
        }

        $resources = $request->attributes->get('_resources');
        if ($operation instanceof CollectionOperationInterface) {
            // Allows to purge collections
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $attributes['resource_class']);
            $iri = $this->iriConverter->getIriFromResource($attributes['resource_class'], UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => $uriVariables]);

            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return;
        }

        if (!$this->purger) {
            $response->headers->set('Cache-Tags', implode(',', $resources));

            return;
        }

        $headers = $this->purger->getResponseHeaders($resources);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
