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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'JsonApiNetworkPathRelationDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    urlGenerationStrategy: UrlGeneratorInterface::NET_PATH,
    operations: [
        new Get(
            uriTemplate: '/jsonapi_network_path_relation_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonapi_network_path_relation_dummies',
            processor: [self::class, 'process'],
        ),
    ],
)]
class NetworkPathRelationDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    /** @var NetworkPathDummy[] */
    public array $networkPathDummies = [];

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
