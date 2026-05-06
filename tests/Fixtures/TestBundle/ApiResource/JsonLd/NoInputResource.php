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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdNoInput',
    operations: [
        new Get(
            uriTemplate: '/jsonld_no_inputs/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_no_inputs',
            input: false,
            processor: [self::class, 'create'],
        ),
        new Post(
            uriTemplate: '/jsonld_no_inputs/{id}/double_bat',
            uriVariables: ['id'],
            input: false,
            status: 200,
            read: true,
            provider: [self::class, 'provide'],
            processor: [self::class, 'doubleBat'],
        ),
    ],
)]
class NoInputResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?int $baz = null;

    public ?string $bat = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->baz = 1;
        $r->bat = 'test';

        return $r;
    }

    public static function create(): self
    {
        $r = new self();
        $r->id = 1;
        $r->baz = 1;
        $r->bat = 'test';

        return $r;
    }

    public static function doubleBat(self $data): self
    {
        $data->bat = (string) $data->bat.$data->bat;

        return $data;
    }
}
