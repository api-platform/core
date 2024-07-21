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
    public function __construct(private readonly ProcessorInterface $decorated, private readonly bool $etag = false, private readonly ?int $maxAge = null, private readonly ?int $sharedMaxAge = null, private readonly ?array $vary = null, private readonly ?bool $public = null, private readonly ?int $staleWhileRevalidate = null, private readonly ?int $staleIfError = null)
    {
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

        if ($this->etag && !$response->getEtag()) {
            $response->setEtag(hash('xxh3', (string) $content));
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

        return $response;
    }
}
