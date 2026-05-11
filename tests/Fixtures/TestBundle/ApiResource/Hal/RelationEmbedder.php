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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

#[ApiResource(
    shortName: 'HalRelationEmbedder',
    operations: [
        new GetCollection(
            uriTemplate: '/hal_relation_embedders',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/hal_relation_embedders/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/hal_relation_embedders',
            processor: [self::class, 'process'],
        ),
        new Put(
            uriTemplate: '/hal_relation_embedders/{id}',
            uriVariables: ['id'],
            extraProperties: ['standard_put' => false],
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
        new Patch(
            uriTemplate: '/hal_relation_embedders/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class RelationEmbedder
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $krondstadt = 'Krondstadt';

    #[ApiProperty(readableLink: true)]
    public ?HalRelatedResource $related = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->related = HalRelatedResource::provide(new Get(), ['id' => 1], $context);

        return $r;
    }

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }
}
