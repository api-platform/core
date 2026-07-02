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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Payment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\VoidPayment;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Ports the @controller-tagged features/main/custom_controller.feature scenarios.
 * Controllers return raw entities or JsonResponse and rely on SerializeListener to
 * wrap them, so they require USE_SYMFONY_LISTENERS=1 (CI: phpunit_listeners job).
 */
final class CustomControllerTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CustomActionDummy::class, Payment::class, VoidPayment::class];
    }

    protected function setUp(): void
    {
        if (!($_SERVER['USE_SYMFONY_LISTENERS'] ?? false)) {
            $this->markTestSkipped('Requires USE_SYMFONY_LISTENERS=1.');
        }

        $this->recreateSchema([CustomActionDummy::class, Payment::class, VoidPayment::class]);
    }

    public function testCustomDenormalizationRoute(): void
    {
        self::createClient()->request('POST', '/custom/denormalization', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomActionDummy',
            '@id' => '/custom_action_dummies/1',
            '@type' => 'CustomActionDummy',
            'id' => 1,
            'foo' => 'custom!',
        ]);
    }

    public function testCustomNormalizationRoute(): void
    {
        $this->seedCustomDummy('custom!');

        $response = self::createClient()->request('GET', '/custom/1/normalization', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['id' => 1, 'foo' => 'foo'], $response->toArray());
    }

    public function testShortCustomDenormalizationRoute(): void
    {
        self::createClient()->request('POST', '/short_custom/denormalization', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomActionDummy',
            '@id' => '/custom_action_dummies/1',
            '@type' => 'CustomActionDummy',
            'id' => 1,
            'foo' => 'short declaration',
        ]);
    }

    public function testShortCustomNormalizationRoute(): void
    {
        $this->seedCustomDummy('custom!');

        $response = self::createClient()->request('GET', '/short_custom/1/normalization', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['id' => 1, 'foo' => 'short'], $response->toArray());
    }

    public function testCustomCollectionWithoutSpecificRoute(): void
    {
        $this->seedCustomDummy('first');
        $this->seedCustomDummy('second');

        $response = self::createClient()->request('GET', '/custom_action_collection_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, $response->toArray()['hydra:member']);
    }

    public function testCustomItemOperationWithoutSpecificRoute(): void
    {
        $this->seedCustomDummy('custom!');

        self::createClient()->request('GET', '/custom_action_collection_dummies/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomActionDummy',
            '@id' => '/custom_action_collection_dummies/1',
            '@type' => 'CustomActionDummy',
            'id' => 1,
            'foo' => 'custom!',
        ]);
    }

    public function testCreatePayment(): void
    {
        self::createClient()->request('POST', '/payments', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
            'json' => ['amount' => '123.45'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/Payment',
            '@id' => '/payments/1',
            '@type' => 'Payment',
            'id' => 1,
            'amount' => '123.45',
            'voidPayment' => null,
        ]);
    }

    public function testVoidPayment(): void
    {
        $this->seedPayment('123.45');

        self::createClient()->request('POST', '/payments/1/void', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/VoidPayment',
            '@id' => '/void_payments/1',
            '@type' => 'VoidPayment',
            'id' => 1,
            'payment' => '/payments/1',
        ]);
    }

    public function testGetVoidPayment(): void
    {
        $this->seedPayment('123.45');
        self::createClient()->request('POST', '/payments/1/void', [
            'headers' => ['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'],
        ]);

        self::createClient()->request('GET', '/void_payments/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/VoidPayment',
            '@id' => '/void_payments/1',
            '@type' => 'VoidPayment',
            'id' => 1,
            'payment' => '/payments/1',
        ]);
    }

    private function seedCustomDummy(string $foo): void
    {
        $manager = $this->getManager();
        $dummy = new CustomActionDummy();
        $dummy->setFoo($foo);
        $manager->persist($dummy);
        $manager->flush();
    }

    private function seedPayment(string $amount): Payment
    {
        $manager = $this->getManager();
        $payment = new Payment($amount);
        $manager->persist($payment);
        $manager->flush();

        return $payment;
    }
}
