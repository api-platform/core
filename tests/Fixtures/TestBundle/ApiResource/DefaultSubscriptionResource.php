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
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [
        new Query(),
        new Subscription(),
        new Subscription(name: 'update'),
        new SubscriptionCollection(),
        new Subscription(name: 'watch', description: 'Custom item description.'),
        new SubscriptionCollection(name: 'watchCollection', description: 'Custom collection description.'),
    ],
    mercure: true,
    extraProperties: ['legacy_graphql_subscription_names' => false],
    provider: [self::class, 'provide'],
)]
final class DefaultSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->id = (int) ($uriVariables['id'] ?? 1);

        return $resource;
    }
}
