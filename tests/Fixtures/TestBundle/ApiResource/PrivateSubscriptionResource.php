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
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [new Get(uriTemplate: '/private_subscription_resources/{id}')],
    graphQlOperations: [
        new Query(name: 'item_query'),
        new Subscription(name: 'update'),
        new Subscription(name: 'watch'),
    ],
    mercure: ['private' => true, 'private_fields' => ['tenant']],
    provider: [self::class, 'provide'],
)]
final class PrivateSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
    public string $tenant;
    public string $name = 'Initial';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->id = (int) $uriVariables['id'];
        $resource->tenant = $resource->id < 3 ? 'tenant-a' : 'tenant-b';

        return $resource;
    }
}
