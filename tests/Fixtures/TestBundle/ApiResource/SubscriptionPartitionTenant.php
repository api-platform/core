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
use ApiPlatform\Metadata\Link;

#[ApiResource(
    operations: [new Get(uriTemplate: '/subscription_partition_tenants/{region}/{code}', uriVariables: [
        'region' => new Link(fromClass: self::class, identifiers: ['region']),
        'code' => new Link(fromClass: self::class, identifiers: ['code']),
    ])],
    graphQlOperations: [],
)]
final class SubscriptionPartitionTenant implements \Stringable
{
    #[ApiProperty(identifier: true)]
    public string $region;

    #[ApiProperty(identifier: true)]
    public string $code = '42';

    public function __toString(): string
    {
        return 'Shared display name';
    }
}
