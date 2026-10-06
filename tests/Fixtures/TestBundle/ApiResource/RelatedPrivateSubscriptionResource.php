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

#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [new Query(), new Subscription(name: 'watch')],
    mercure: ['private' => true, 'private_fields' => ['tenant']],
)]
final class RelatedPrivateSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;
    public string $name = 'Initial';
    public SubscriptionPartitionTenant $tenant;
}
