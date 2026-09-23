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
use ApiPlatform\Tests\Fixtures\TestBundle\Parameter\RateLimitHeaderProvider;

#[Get(
    uriTemplate: 'with_response_headers/{id}',
    responseHeaders: [
        'RateLimit-Limit' => new ResponseHeaderParameter(
            schema: ['type' => 'integer'],
            description: 'Maximum number of requests per window',
            provider: RateLimitHeaderProvider::class,
        ),
        'RateLimit-Remaining' => new ResponseHeaderParameter(
            schema: ['type' => 'integer'],
            description: 'Remaining requests in current window',
            provider: RateLimitHeaderProvider::class,
        ),
        'X-Static-Header' => new ResponseHeaderParameter(
            schema: ['type' => 'string'],
            description: 'Static header value',
            default: 'static-value',
        ),
        'X-Empty-Header' => new ResponseHeaderParameter(
            description: 'Resolved to an empty value',
            provider: [self::class, 'provideEmptyValue'],
        ),
        'X-Frame-Options' => new ResponseHeaderParameter(
            description: 'Never emitted, the provider resolves no value',
            provider: [self::class, 'provideNoValue'],
            openApi: false,
        ),
    ],
    provider: [self::class, 'provide'],
)]
#[Get(
    uriTemplate: 'with_streamed_response_headers/{id}',
    jsonStream: true,
    responseHeaders: [
        'RateLimit-Limit' => new ResponseHeaderParameter(
            schema: ['type' => 'integer'],
            description: 'Maximum number of requests per window',
            provider: RateLimitHeaderProvider::class,
        ),
    ],
    provider: [self::class, 'provide'],
)]
#[Post(
    uriTemplate: 'with_response_headers',
    responseHeaders: [
        'RateLimit-Limit' => new ResponseHeaderParameter(
            schema: ['type' => 'integer'],
            description: 'Maximum number of requests per window',
            provider: RateLimitHeaderProvider::class,
        ),
    ],
    provider: [self::class, 'provide'],
)]
class WithResponseHeader
{
    public function __construct(public readonly string $id = '1', public readonly string $name = 'hello')
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self($uriVariables['id'] ?? '1');
    }

    public static function provideEmptyValue(Parameter $parameter): null
    {
        $parameter->setValue('');

        return null;
    }

    public static function provideNoValue(): null
    {
        return null;
    }
}
