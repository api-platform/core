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

namespace ApiPlatform\Core\Tests\GraphQl\Subscription;

use ApiPlatform\Core\GraphQl\Subscription\SubscriptionIdentifierGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SubscriptionIdentifierGeneratorTest extends TestCase
{
    private $subscriptionIdentifierGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriptionIdentifierGenerator = new SubscriptionIdentifierGenerator();
    }

    public function testDifferentGeneratedSubscriptionIdentifiers(): void
    {
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier();

        $this->assertNotSame($subscriptionId, $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier());
    }
}
