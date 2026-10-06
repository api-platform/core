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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubscriptionNamingTest extends TestCase
{
    #[DataProvider('subscriptionNames')]
    public function testSubscriptionName(Subscription $operation, string $name): void
    {
        $this->assertSame($name, $operation->getName());
    }

    public static function subscriptionNames(): iterable
    {
        yield 'legacy default' => [new Subscription(), 'update_subscription'];
        yield 'explicit legacy flag' => [new Subscription(extraProperties: ['legacy_graphql_subscription_names' => true]), 'update_subscription'];
        yield 'new default' => [new Subscription(extraProperties: ['legacy_graphql_subscription_names' => false]), 'item'];
        yield 'explicit public legacy name' => [new Subscription(name: 'update', extraProperties: ['legacy_graphql_subscription_names' => false]), 'update'];
        yield 'explicit internal legacy name' => [new Subscription(name: 'update_subscription', extraProperties: ['legacy_graphql_subscription_names' => false]), 'update_subscription'];
        yield 'name set with withName' => [(new Subscription(extraProperties: ['legacy_graphql_subscription_names' => false]))->withName('watch'), 'watch'];
        yield 'collection default' => [new SubscriptionCollection(), 'collection'];
        yield 'explicit collection name' => [new SubscriptionCollection(name: 'watch'), 'watch'];
    }
}
