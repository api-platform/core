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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\StopwatchAwareInterface;
use ApiPlatform\State\StopwatchAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds an ETag and revalidating Cache-Control headers on the API documentation
 * and entrypoint responses so clients can avoid re-downloading the (often large)
 * payload when nothing changed.
 *
 * @template T1
 * @template T2
 *
 * @implements ProcessorInterface<T1, T2>
 */
final class CacheableDocumentationProcessor implements ProcessorInterface, StopwatchAwareInterface
{
    use StopwatchAwareTrait;

    /**
     * @param ProcessorInterface<T1, T2> $decorated
     */
    public function __construct(private readonly ProcessorInterface $decorated)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $response = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (!$response instanceof Response || 200 !== $response->getStatusCode()) {
            return $response;
        }

        $content = $response->getContent();
        if (false === $content || '' === $content) {
            return $response;
        }

        $this->stopwatch?->start('api_platform.processor.cacheable_documentation');

        $response->setEtag(md5($content));
        $response->setPublic();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');

        if (($request = $context['request'] ?? null) instanceof Request) {
            $response->isNotModified($request);
        }

        $this->stopwatch?->stop('api_platform.processor.cacheable_documentation');

        return $response;
    }
}
