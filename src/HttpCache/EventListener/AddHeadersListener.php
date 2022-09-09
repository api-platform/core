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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Configures cache HTTP headers for the current response.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddHeadersListener
{
    use OperationRequestInitiatorTrait;

    private $etag;
    private $maxAge;
    private $sharedMaxAge;
    private $vary;
    private $public;
    private $resourceMetadataFactory;
    private $staleWhileRevalidate;
    private $staleIfError;

    /**
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
     */
    public function __construct(bool $etag = false, int $maxAge = null, int $sharedMaxAge = null, array $vary = null, bool $public = null, $resourceMetadataFactory = null, int $staleWhileRevalidate = null, int $staleIfError = null)
    {
        $this->etag = $etag;
        $this->maxAge = $maxAge;
        $this->sharedMaxAge = $sharedMaxAge;
        $this->vary = $vary;
        $this->public = $public;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->staleWhileRevalidate = $staleWhileRevalidate;
        $this->staleIfError = $staleIfError;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->isMethodCacheable()) {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);
        if (\count($attributes) < 1) {
            return;
        }

        $response = $event->getResponse();

        if (!$response->getContent() || !$response->isSuccessful()) {
            return;
        }

        $operation = $this->initializeOperation($request);
        $resourceCacheHeaders = $attributes['cache_headers'] ?? [];

        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
            $resourceCacheHeaders = $resourceMetadata->getOperationAttribute($attributes, 'cache_headers', [], true);
        } elseif ($operation) {
            $resourceCacheHeaders = $operation->getCacheHeaders();
        }

        if ($this->etag && !$response->getEtag()) {
            $response->setEtag(md5((string) $response->getContent()));
        }

        if (null !== ($maxAge = $resourceCacheHeaders['max_age'] ?? $this->maxAge) && !$response->headers->hasCacheControlDirective('max-age')) {
            $response->setMaxAge($maxAge);
        }

        $vary = $resourceCacheHeaders['vary'] ?? $this->vary;
        if (null !== $vary) {
            $response->setVary(array_diff($vary, $response->getVary()), false);
        }

        // if the public-property is defined and not yet set; apply it to the response
        $public = ($resourceCacheHeaders['public'] ?? $this->public);
        if (null !== $public && !$response->headers->hasCacheControlDirective('public')) {
            $public ? $response->setPublic() : $response->setPrivate();
        }

        // Cache-Control "s-maxage" is only relevant is resource is not marked as "private"
        if (false !== $public && null !== ($sharedMaxAge = $resourceCacheHeaders['shared_max_age'] ?? $this->sharedMaxAge) && !$response->headers->hasCacheControlDirective('s-maxage')) {
            $response->setSharedMaxAge($sharedMaxAge);
        }

        if (null !== ($staleWhileRevalidate = $resourceCacheHeaders['stale_while_revalidate'] ?? $this->staleWhileRevalidate) && !$response->headers->hasCacheControlDirective('stale-while-revalidate')) {
            $response->headers->addCacheControlDirective('stale-while-revalidate', (string) $staleWhileRevalidate);
        }

        if (null !== ($staleIfError = $resourceCacheHeaders['stale_if_error'] ?? $this->staleIfError) && !$response->headers->hasCacheControlDirective('stale-if-error')) {
            $response->headers->addCacheControlDirective('stale-if-error', (string) $staleIfError);
        }
    }
}

class_alias(AddHeadersListener::class, \ApiPlatform\Core\HttpCache\EventListener\AddHeadersListener::class);
