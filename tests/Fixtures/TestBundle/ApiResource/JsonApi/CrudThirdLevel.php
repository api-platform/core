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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonApiThirdLevel',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_third_levels',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_third_levels/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonapi_third_levels',
            processor: [self::class, 'process'],
        ),
    ],
)]
class CrudThirdLevel
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public int $level = 3;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}
