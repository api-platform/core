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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'JsonApiNetworkPathDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    urlGenerationStrategy: UrlGeneratorInterface::NET_PATH,
    paginationItemsPerPage: 3,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_network_path_dummies',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_network_path_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new GetCollection(
            uriTemplate: '/jsonapi_network_path_relation_dummies/{relationId}/network_path_dummies',
            uriVariables: [
                'relationId' => new Link(fromClass: NetworkPathRelationDummy::class, identifiers: ['id']),
            ],
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class NetworkPathDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public ?NetworkPathRelationDummy $networkPathRelationDummy = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->networkPathRelationDummy = NetworkPathRelationDummy::provide($operation, ['id' => 1], $context);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }
}
