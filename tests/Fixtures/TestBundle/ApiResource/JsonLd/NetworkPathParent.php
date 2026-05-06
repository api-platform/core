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
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'JsonLdNetworkPathParent',
    urlGenerationStrategy: UrlGeneratorInterface::NET_PATH,
    operations: [
        new Get(
            uriTemplate: '/jsonld_network_path_parents/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_network_path_parents',
            processor: [self::class, 'process'],
        ),
    ],
)]
class NetworkPathParent
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }

    public static function process(self $data): self
    {
        $data->id = 2;

        return $data;
    }
}
