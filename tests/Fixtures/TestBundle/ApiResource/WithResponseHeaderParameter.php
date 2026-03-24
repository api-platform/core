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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ResponseHeaderParameter;

#[Get(
    uriTemplate: 'with_response_headers/{id}',
    parameters: [
        'RateLimit-Limit' => new ResponseHeaderParameter(schema: ['type' => 'integer'], description: 'Maximum number of requests per window', provider: [self::class, 'provideRateLimitHeaders']),
        'RateLimit-Remaining' => new ResponseHeaderParameter(schema: ['type' => 'integer'], description: 'Remaining requests in current window', provider: [self::class, 'provideRateLimitHeaders']),
    ],
    provider: [self::class, 'provide'],
)]
#[Post(
    uriTemplate: 'with_response_headers',
    parameters: [
        'RateLimit-Limit' => new ResponseHeaderParameter(schema: ['type' => 'integer'], description: 'Maximum number of requests per window'),
        'RateLimit-Remaining' => new ResponseHeaderParameter(schema: ['type' => 'integer'], description: 'Remaining requests in current window'),
    ],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
class WithResponseHeaderParameter
{
    public function __construct(public readonly string $id = '1', public readonly string $name = 'hello')
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self($uriVariables['id'] ?? '1');
    }

    public static function provideRateLimitHeaders(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        if ('RateLimit-Limit' === $parameter->getKey()) {
            $parameter->setValue(100);
        }
        if ('RateLimit-Remaining' === $parameter->getKey()) {
            $parameter->setValue(99);
        }

        return $context['operation'] ?? null;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        foreach ($operation->getParameters() ?? [] as $parameter) {
            if ('RateLimit-Limit' === $parameter->getKey()) {
                $parameter->setValue(50);
            }
            if ('RateLimit-Remaining' === $parameter->getKey()) {
                $parameter->setValue(49);
            }
        }

        return $data;
    }
}
