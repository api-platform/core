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
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [
        new Query(),
        new SubscriptionCollection(name: 'missing', mercure: ['private' => true, 'private_fields' => ['missingTenant']]),
        new SubscriptionCollection(name: 'partial', mercure: ['private' => true, 'private_fields' => ['tenant', 'missingTenant']]),
        new SubscriptionCollection(name: 'noRead', read: false, mercure: ['private' => true, 'private_fields' => ['tenant']]),
        new SubscriptionCollection(name: 'nullable', mercure: ['private' => true, 'private_fields' => ['tenant']]),
    ],
    provider: [self::class, 'provide'],
)]
final class IncompletePrivateSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
    public ?string $tenant = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->id = (int) $uriVariables['id'];

        return $resource;
    }
}
