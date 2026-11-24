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

namespace ApiPlatform\Tests\Functional\HttpCache;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\NullPurger;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedDeliveryDay;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Supplier as SupplierDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeliveryDay;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Supplier;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * @see https://github.com/api-platform/core/issues/8123
 */
final class PurgeTagProviderTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Supplier::class, DeliveryDay::class, MappedDeliveryDay::class];
    }

    public function testSubresourceCollectionIsPurged(): void
    {
        $this->recreateSchema([Supplier::class, DeliveryDay::class]);

        $manager = $this->getManager();
        foreach (['First', 'Second'] as $name) {
            $supplier = $this->isMongoDB() ? new SupplierDocument() : new Supplier();
            $supplier->name = $name;
            $manager->persist($supplier);
        }
        $manager->flush();

        $client = self::createClient();

        $client->request('GET', '/suppliers/1/delivery_days');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Cache-Tags', '/suppliers/1/delivery_days');

        $this->purger()->clear();
        $client->request('POST', '/delivery_days', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['supplier' => '/suppliers/1', 'day' => 'monday'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertContains('/suppliers/1/delivery_days', $this->purger()->getIris());

        // Moving to another supplier purges both the previous and the new parent collection.
        $this->purger()->clear();
        $client->request('PUT', '/delivery_days/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['supplier' => '/suppliers/2', 'day' => 'monday'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertContains('/suppliers/1/delivery_days', $this->purger()->getIris());
        $this->assertContains('/suppliers/2/delivery_days', $this->purger()->getIris());

        $this->purger()->clear();
        $client->request('DELETE', '/delivery_days/1');
        $this->assertResponseStatusCodeSame(204);
        $this->assertContains('/suppliers/2/delivery_days', $this->purger()->getIris());
        $this->assertContains('/delivery_days/1/history', $this->purger()->getIris());
    }

    public function testMappedResourceUpdateReceivesEntityAndPreviousResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The ObjectMapper stateOptions entity is ORM only.');
        }

        $this->recreateSchema([Supplier::class, DeliveryDay::class]);

        $deliveryDay = new DeliveryDay();
        $deliveryDay->day = 'monday';
        $manager = $this->getManager();
        $manager->persist($deliveryDay);
        $manager->flush();

        $this->purger()->clear();
        self::createClient()->request('PUT', '/mapped_delivery_days/'.$deliveryDay->id, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['day' => 'tuesday'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertContains('/mapped_delivery_days/day/monday', $this->purger()->getIris());
        $this->assertContains('/mapped_delivery_days/day/tuesday', $this->purger()->getIris());
    }

    private function purger(): NullPurger
    {
        $purger = static::getContainer()->get('test.api_platform.http_cache.purger');
        \assert($purger instanceof NullPurger);

        return $purger;
    }
}
