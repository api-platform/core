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

namespace ApiPlatform\Core\HttpCache\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Configures cache HTTP headers for the current response.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class AddHeadersListener
{
    private $etag;
    private $maxAge;
    private $sharedMaxAge;
    private $vary;
    private $public;
    private $resourceMetadataFactory;

    public function __construct(bool $etag = false, int $maxAge = null, int $sharedMaxAge = null, array $vary = null, bool $public = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->etag = $etag;
        $this->maxAge = $maxAge;
        $this->sharedMaxAge = $sharedMaxAge;
        $this->vary = $vary;
        $this->public = $public;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    public function handleEvent(/*EventInterface */ $event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
            $response = $event->getContext()['response'];
        } elseif ($event instanceof FilterResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', FilterResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
            $response = $event->getResponse();
        } else {
            return;
        }

        if (!$request->isMethodCacheable() || !RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        if (!$response->getContent() || !$response->isSuccessful()) {
            return;
        }

        $resourceCacheHeaders = [];
        if ($this->resourceMetadataFactory && $attributes = RequestAttributesExtractor::extractAttributes($request)) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
            $resourceCacheHeaders = $resourceMetadata->getOperationAttribute($attributes, 'cache_headers', [], true);
        }

        if ($this->etag && !$response->getEtag()) {
            $response->setEtag(md5($response->getContent()));
        }

        if (null !== ($maxAge = $resourceCacheHeaders['max_age'] ?? $this->maxAge) && !$response->headers->hasCacheControlDirective('max-age')) {
            $response->setMaxAge($maxAge);
        }

        if (null !== $this->vary) {
            $response->setVary(array_diff($this->vary, $response->getVary()), false);
        }

        if (null !== ($sharedMaxAge = $resourceCacheHeaders['shared_max_age'] ?? $this->sharedMaxAge) && !$response->headers->hasCacheControlDirective('s-maxage')) {
            $response->setSharedMaxAge($sharedMaxAge);
        }

        if (null !== $this->public && !$response->headers->hasCacheControlDirective('public')) {
            $this->public ? $response->setPublic() : $response->setPrivate();
        }
    }
}
