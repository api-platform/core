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
use ApiPlatform\Metadata\GraphQl\Subscription;

#[ApiResource(mercure: true)]
#[ApiResource(
    shortName: 'OperationSubscriptionResource',
    graphQlOperations: [
        new Subscription(extraProperties: ['legacy_graphql_subscription_names' => false]),
        new Subscription(name: 'update_subscription'),
    ],
    mercure: true,
    extraProperties: ['legacy_graphql_subscription_names' => true],
)]
final class AutomaticSubscriptionResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
}
