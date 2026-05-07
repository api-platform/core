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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'HalNetworkPathChild',
    urlGenerationStrategy: UrlGeneratorInterface::NET_PATH,
    operations: [
        new GetCollection(
            uriTemplate: '/hal_network_path_children',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/hal_network_path_children/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/hal_network_path_children',
            processor: [self::class, 'process'],
        ),
        new GetCollection(
            uriTemplate: '/hal_network_path_parents/{parentId}/children',
            uriVariables: [
                'parentId' => new Link(fromClass: NetworkPathParent::class, identifiers: ['id']),
            ],
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class NetworkPathResource
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public ?NetworkPathParent $parent = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->parent = NetworkPathParent::provide($operation, ['id' => 1], $context);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }

    public static function process(self $data): self
    {
        $data->id = 2;

        return $data;
    }
}
