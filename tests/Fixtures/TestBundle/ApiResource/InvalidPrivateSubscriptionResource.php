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
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;

#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [new SubscriptionCollection(name: 'watch')],
    mercure: ['private_fields' => ['tenant']],
)]
final class InvalidPrivateSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
    public string $tenant;
}
