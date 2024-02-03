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

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Configures cache HTTP headers for the current response.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated use \Symfony\EventListener\AddHeadersListener.php instead
 */
final class AddHeadersListener
{
    use OperationRequestInitiatorTrait;

    public function __construct(private readonly bool $etag = false, private readonly ?int $maxAge = null, private readonly ?int $sharedMaxAge = null, private readonly ?array $vary = null, private readonly ?bool $public = null, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, private readonly ?int $staleWhileRevalidate = null, private readonly ?int $staleIfError = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
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
        if ('api_platform.symfony.main_controller' === $operation?->getController()) {
            return;
        }
        $resourceCacheHeaders = $attributes['cache_headers'] ?? $operation?->getCacheHeaders() ?? [];

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
