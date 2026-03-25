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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [new Get(), new GetCollection()],
    graphQlOperations: [
        new Query(name: 'item_query'),
        new QueryCollection(name: 'collection_query'),
        new Subscription(mercure: true, name: 'update'),
        new SubscriptionCollection(mercure: true, name: 'update_collection'),
    ],
    provider: [self::class, 'provide'],
)]
final class GraphQlSubscriptionPair
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->id = isset($uriVariables['id']) ? (int) $uriVariables['id'] : 1;

        return $resource;
    }
}
