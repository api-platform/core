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
use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\HttpCache\TagsHeadersProvider;
use ApiPlatform\HttpCache\TagsHeadersProviderInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Adds the IRI list of the request’s resources in the response’s headers.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddTagsListener
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, private PurgerInterface|TagsHeadersProviderInterface|null $headersProvider = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;

        if ($this->headersProvider instanceof TagsHeadersProvider) {
            return;
        }

        trigger_deprecation('api-platform/core', '3.1', 'Not passing $headersProvider a %s is deprecated.', TagsHeadersProvider::class);

        if (!$this->headersProvider) {
            $this->headersProvider = new TagsHeadersProvider('Cache-Tags', ',');
        }
    }

    /**
     * Adds tags to the response.
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
        if ($operation instanceof CollectionOperationInterface) {
            // Allows to purge collections
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $attributes['resource_class']);
            $iri = $this->iriConverter->getIriFromResource($attributes['resource_class'], UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => $uriVariables]);

            $resources[$iri] = $iri;
        }

        if (!$resources) {
            return;
        }

        $headers = $this->headersProvider instanceof PurgerInterface
            ? $this->headersProvider->getResponseHeaders($resources)
            : $this->headersProvider->provideHeaders($resources);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
