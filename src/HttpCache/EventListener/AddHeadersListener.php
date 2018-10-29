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

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, bool $etag = false, int $maxAge = null, int $sharedMaxAge = null, array $vary = null, bool $public = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->etag = $etag;
        $this->maxAge = $maxAge;
        $this->sharedMaxAge = $sharedMaxAge;
        $this->vary = $vary;
        $this->public = $public;
    }

    /**
     * @param FilterResponseEvent $event
     * @throws \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->isMethodCacheable() || !RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $response = $event->getResponse();

        if (!$response->getContent()) {
            return;
        }

        $resourceCacheHeaders = [];
        if ($this->resourceMetadataFactory && $attributes = RequestAttributesExtractor::extractAttributes($request)) {
            $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
            $resourceCacheHeaders = $resourceMetadata->getAttribute('cache_headers');
        }

        if (!$response->getEtag() && $this->etag) {
            $response->setEtag(md5($response->getContent()));
        }

        if (!$response->headers->hasCacheControlDirective('max-age') && (($useMeta = isset($resourceCacheHeaders['max_age'])) || null !== $this->maxAge)) {
            $response->setMaxAge($useMeta ? $resourceCacheHeaders['max_age'] : $this->maxAge);
        }

        if (null !== $this->vary) {
            $response->setVary(array_diff($this->vary, $response->getVary()), false);
        }

        if (!$response->headers->hasCacheControlDirective('s-maxage') && (($useMeta = isset($resourceCacheHeaders['shared_max_age'])) || null !== $this->sharedMaxAge)) {
            $response->setSharedMaxAge($useMeta ? $resourceCacheHeaders['shared_max_age'] : $this->sharedMaxAge);
        }

        if (!$response->headers->hasCacheControlDirective('public') && !$response->headers->hasCacheControlDirective('private') && null !== $this->public) {
            $this->public ? $response->setPublic() : $response->setPrivate();
        }
    }
}
