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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeCar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeVehicle;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri\SharedTypeVehicleStore;
use ApiPlatform\Tests\Functional\DiscriminatorMapAppKernel;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class SharedTypeIriTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected static function getKernelClass(): string
    {
        return DiscriminatorMapAppKernel::class;
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SharedTypeVehicle::class, SharedTypeCar::class, SharedTypeTruck::class];
    }

    protected function tearDown(): void
    {
        SharedTypeVehicleStore::$vehicles = [];

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function kindProvider(): iterable
    {
        yield 'car' => ['car', SharedTypeCar::class];
        yield 'truck' => ['truck', SharedTypeTruck::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('kindProvider')]
    public function testTypeSharedByTheSubtypesDoesNotContradictAnyOfThem(string $kind, string $class): void
    {
        self::createClient()->request('POST', '/shared_type_vehicles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['@type' => 'https://schema.org/Vehicle', 'kind' => $kind, 'name' => 'Shared'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($class, SharedTypeVehicleStore::$vehicles[1]::class);
    }
}
