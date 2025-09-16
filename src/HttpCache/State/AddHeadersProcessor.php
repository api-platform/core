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

namespace ApiPlatform\HttpCache\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class AddHeadersProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly bool $etag = false,
        private readonly ?int $maxAge = null,
        private readonly ?int $sharedMaxAge = null,
        private readonly ?array $vary = null,
        private readonly ?bool $public = null,
        private readonly ?int $staleWhileRevalidate = null,
        private readonly ?int $staleIfError = null,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (
            !($request = $context['request'] ?? null)
            || !$request->isMethodCacheable()
            || !$response instanceof Response
            || !$operation instanceof HttpOperation
        ) {
            return $response;
        }

        if (!($content = $response->getContent()) || !$response->isSuccessful()) {
            return $response;
        }

        $resourceCacheHeaders = $operation->getCacheHeaders() ?? [];

        $public = ($resourceCacheHeaders['public'] ?? $this->public);

        $options = [
            'etag' => $this->etag && !$response->getEtag() ? hash('xxh3', (string) $content) : null,
            'max_age' => null !== ($maxAge = $resourceCacheHeaders['max_age'] ?? $this->maxAge) && !$response->headers->hasCacheControlDirective('max-age') ? $maxAge : null,
            // Cache-Control "s-maxage" is only relevant is resource is not marked as "private"
            's_maxage' => false !== $public && null !== ($sharedMaxAge = $resourceCacheHeaders['shared_max_age'] ?? $this->sharedMaxAge) && !$response->headers->hasCacheControlDirective('s-maxage') ? $sharedMaxAge : null,
            'public' => null !== $public && !$response->headers->hasCacheControlDirective('public') ? $public : null,
            'stale_while_revalidate' => null !== ($staleWhileRevalidate = $resourceCacheHeaders['stale_while_revalidate'] ?? $this->staleWhileRevalidate) && !$response->headers->hasCacheControlDirective('stale-while-revalidate') ? $staleWhileRevalidate : null,
            'stale_if_error' => null !== ($staleIfError = $resourceCacheHeaders['stale_if_error'] ?? $this->staleIfError) && !$response->headers->hasCacheControlDirective('stale-if-error') ? $staleIfError : null,
            'must_revalidate' => null !== ($mustRevalidate = $resourceCacheHeaders['must_revalidate'] ?? null) && !$response->headers->hasCacheControlDirective('must-revalidate') ? $mustRevalidate : null,
            'proxy_revalidate' => null !== ($proxyRevalidate = $resourceCacheHeaders['proxy_revalidate'] ?? null) && !$response->headers->hasCacheControlDirective('proxy-revalidate') ? $proxyRevalidate : null,
            'no_cache' => null !== ($noCache = $resourceCacheHeaders['no_cache'] ?? null) && !$response->headers->hasCacheControlDirective('no-cache') ? $noCache : null,
            'no_store' => null !== ($noStore = $resourceCacheHeaders['no_store'] ?? null) && !$response->headers->hasCacheControlDirective('no-store') ? $noStore : null,
            'no_transform' => null !== ($noTransform = $resourceCacheHeaders['no_transform'] ?? null) && !$response->headers->hasCacheControlDirective('no-transform') ? $noTransform : null,
            'immutable' => null !== ($immutable = $resourceCacheHeaders['immutable'] ?? null) && !$response->headers->hasCacheControlDirective('immutable') ? $immutable : null,
        ];

        $response->setCache($options);

        $vary = $resourceCacheHeaders['vary'] ?? $this->vary;
        if (null !== $vary) {
            $response->setVary(array_diff($vary, $response->getVary()), false);
        }

        return $response;
    }
}
