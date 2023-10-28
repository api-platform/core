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

namespace ApiPlatform\GraphQl\Tests\Subscription;

use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SubscriptionIdentifierGeneratorTest extends TestCase
{
    private SubscriptionIdentifierGenerator $subscriptionIdentifierGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriptionIdentifierGenerator = new SubscriptionIdentifierGenerator();
    }

    public function testGenerateSubscriptionIdentifier(): void
    {
        $this->assertSame('bf861b4e0edd7766ff61da90c60fdceef2618b595a3628901921d4d8eca555d0', $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier([
            'dummyMercure' => [
                'id' => true,
                'name' => true,
                'relatedDummy' => [
                    'name' => true,
                ],
            ],
        ]));
    }

    public function testGenerateSubscriptionIdentifierFieldsNotIncluded(): void
    {
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier([
            'dummyMercure' => [
                'id' => true,
                'name' => true,
                'relatedDummy' => [
                    'name' => true,
                ],
            ],
        ]);

        $subscriptionId2 = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier([
            'dummyMercure' => [
                'id' => true,
                'name' => true,
                'relatedDummy' => [
                    'name' => true,
                ],
            ],
            'mercureUrl' => true,
            'clientSubscriptionId' => true,
        ]);

        $this->assertSame($subscriptionId, $subscriptionId2);
    }

    public function testDifferentGeneratedSubscriptionIdentifiers(): void
    {
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier([
            'dummyMercure' => [
                'id' => true,
                'name' => true,
                'relatedDummy' => [
                    'name' => true,
                ],
            ],
        ]);

        $this->assertNotSame($subscriptionId, $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier([
            'dummyMercure' => [
                'id' => true,
                'relatedDummy' => [
                    'name' => true,
                ],
            ],
        ]));
    }
}
