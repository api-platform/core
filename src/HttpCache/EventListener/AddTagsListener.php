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
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\HttpCache\PurgerInterface as LegacyPurgerInterface;
use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
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
 */
final class AddTagsListener
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    private $iriConverter;
    private $purger;

    /**
     * @param LegacyPurgerInterface|PurgerInterface|null        $purger
     * @param LegacyIriConverterInterface|IriConverterInterface $iriConverter
     * @param mixed|null                                        $purger
     */
    public function __construct($iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, $purger = null)
    {
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->purger = $purger;
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
        if (isset($attributes['collection_operation_name']) || ($attributes['subresource_context']['collection'] ?? false) || ($operation && $operation instanceof CollectionOperationInterface)) {
            // Allows to purge collections
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $attributes['resource_class']);

            if ($this->iriConverter instanceof LegacyIriConverterInterface) {
                $iri = $this->iriConverter->getIriFromResourceClass($attributes['resource_class'], UrlGeneratorInterface::ABS_PATH);
            } else {
                $iri = $this->iriConverter->getIriFromResource($attributes['resource_class'], UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => $uriVariables]);
            }

            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return;
        }

        if ($this->purger instanceof LegacyPurgerInterface || !$this->purger) {
            $response->headers->set('Cache-Tags', implode(',', $resources));
            trigger_deprecation('api-platform/core', '2.7', sprintf('The interface "%s" is deprecated, use "%s" instead.', LegacyPurgerInterface::class, PurgerInterface::class));

            return;
        }

        $headers = $this->purger->getResponseHeaders($resources);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}

class_alias(AddTagsListener::class, \ApiPlatform\Core\HttpCache\EventListener\AddTagsListener::class);
