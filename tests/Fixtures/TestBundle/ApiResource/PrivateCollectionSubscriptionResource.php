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
        new SubscriptionCollection(name: 'watch'),
        new SubscriptionCollection(name: 'secured', security: 'object != null and object.tenant == "tenant-a"'),
    ],
    mercure: ['private' => true, 'private_fields' => ['tenant']],
    provider: [self::class, 'provide'],
)]
final class PrivateCollectionSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
    public string $tenant;
    public string $name = 'Initial';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        $id = (int) ($uriVariables['id'] ?? 0);
        if ($id < 1 || $id > 4) {
            return null;
        }

        $resource = new self();
        $resource->id = $id;
        $resource->tenant = $id < 3 ? 'tenant-a' : 'tenant-b';

        return $resource;
    }
}
