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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WildcardAcceptFormat;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpFoundation\Response;

#[Get(
    shortName: 'WildcardAcceptFormat',
    uriTemplate: '/wildcard_accept_format/{id}',
    outputFormats: ['jsonld' => ['application/ld+json'], 'html' => ['text/html']],
    provider: [self::class, 'provide'],
    extraProperties: ['_api_disable_swagger_provider' => true]
)]
class WildcardAcceptFormat
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $name = 'hello';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|Response
    {
        if ('html' === $context['request']?->getRequestFormat()) {
            return new Response('<h1>hello</h1>', 200, ['Content-Type' => 'text/html']);
        }

        return new self();
    }
}
