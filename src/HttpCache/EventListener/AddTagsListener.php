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

namespace ApiPlatform\HttpCache\EventListener;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Sets the list of resources' IRIs included in this response in the "Cache-Tags" and/or "xkey" HTTP headers.
 *
 * The "Cache-Tags" is used because it is supported by CloudFlare.
 *
 * @see https://support.cloudflare.com/hc/en-us/articles/206596608-How-to-Purge-Cache-Using-Cache-Tags-Enterprise-only-
 *
 * The "xkey" is used because it is supported by Varnish.
 * @see https://docs.varnish-software.com/varnish-cache-plus/vmods/ykey/
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class AddTagsListener
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    private $iriConverter;
    private $xkeyEnabled;
    private $xkeyGlue;
    private $httpTagsEnabled;

    public function __construct($iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, bool $xkeyEnabled = false, string $xkeyGlue = ' ', bool $httpTagsEnabled = true)
    {
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->xkeyEnabled = $xkeyEnabled;
        $this->xkeyGlue = $xkeyGlue;
        $this->httpTagsEnabled = $httpTagsEnabled;
    }

    /**
     * Adds the "Cache-Tags" and "xkey" headers.
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
        ) {
            return;
        }

        $resources = $request->attributes->get('_resources');
        if (isset($attributes['collection_operation_name']) || ($attributes['subresource_context']['collection'] ?? false) || ($operation && $operation->isCollection())) {
            // Allows to purge collections
            $identifiers = $this->getOperationIdentifiers($operation, $request->attributes->all(), $attributes['resource_class']);
            $iri = $this->iriConverter instanceof IriConverterInterface ? $this->iriConverter->getIriFromResourceClass($attributes['resource_class'], $attributes['operation_name'] ?? null, UrlGeneratorInterface::ABS_PATH, ['identifiers_values' => $identifiers]) : $this->iriConverter->getIriFromResourceClass($attributes['resource_class'], UrlGeneratorInterface::ABS_PATH);
            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return;
        }

        if ($this->httpTagsEnabled) {
            $response->headers->set('Cache-Tags', implode(',', $resources));
        }

        if ($this->xkeyEnabled) {
            $response->headers->set('xkey', implode($this->xkeyGlue, $resources));
        }
    }
}
