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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubtypeFilter\SubtypeFilterBicycle;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubtypeFilter\SubtypeFilterCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubtypeFilter\SubtypeFilterSportsCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubtypeFilter\SubtypeFilterTruck;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubtypeFilter\SubtypeFilterVehicle;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class SubtypeFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SubtypeFilterVehicle::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('SubtypeFilter is only available for Doctrine ORM.');
        }

        $this->recreateSchema([
            SubtypeFilterVehicle::class,
            SubtypeFilterCar::class,
            SubtypeFilterSportsCar::class,
            SubtypeFilterTruck::class,
            SubtypeFilterBicycle::class,
        ]);
        $this->loadFixtures();
    }

    /**
     * @param list<string> $expectedNames
     */
    #[DataProvider('provideFilterCases')]
    public function testFilter(string $query, array $expectedNames): void
    {
        $response = self::createClient()->request('GET', '/subtype_filter_vehicles?'.$query);
        $this->assertResponseIsSuccessful();

        $names = array_map(static fn (array $member): string => $member['name'], $response->toArray()['hydra:member']);
        sort($names);
        $this->assertSame($expectedNames, $names);
    }

    public static function provideFilterCases(): iterable
    {
        yield 'subclass property' => ['seats=4', ['car-4']];
        yield 'subclass property matches its own subclasses' => ['seats=2', ['sports']];
        yield 'subclass property with a list of values' => ['seats[]=4&seats[]=2', ['car-4', 'sports']];
        yield 'no match' => ['seats=12', []];
        yield 'non strict keeps rows of other types' => ['seatsOrOtherType=4', ['bike', 'car-4', 'cargo-bike', 'truck']];
        yield 'mapped superclass with a decorated comparison filter' => ['horsepower[gte]=300', ['sports', 'truck']];
        yield 'all conditions of the decorated filter apply to the same subquery' => ['horsepower[gt]=100&horsepower[lt]=400', ['car-7', 'truck']];
        yield 'interface implemented by unrelated subclasses' => ['cargoVolume[]=1&cargoVolume[]=40', ['cargo-bike', 'truck']];
        yield 'interface implemented by unrelated subclasses single value' => ['cargoVolume=40', ['truck']];
        yield 'combined with a regular filter' => ['seatsOrOtherType=7&name=bike', ['bike']];
        yield 'wrapped in OrFilter always behaves as strict' => ['nameOr=car-4&gearsOr=21', ['bike', 'car-4']];
        yield 'decorated filter ignoring the value adds no condition' => ['horsepower[foo]=1', ['bike', 'car-4', 'car-7', 'cargo-bike', 'sports', 'truck']];
    }

    public function testUnknownSubtypeThrows(): void
    {
        self::createClient()->request('GET', '/subtype_filter_vehicles?unknownSubtype=car-4');
        $this->assertResponseStatusCodeSame(500);
        $this->assertJsonContains(['detail' => \sprintf('The subtype "stdClass" does not match any mapped entity inheriting from "%s".', SubtypeFilterVehicle::class)]);
    }

    public function testOpenApiParametersAreDelegatedToTheDecoratedFilter(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $parameterNames = array_column($response->toArray()['paths']['/subtype_filter_vehicles']['get']['parameters'], 'name');

        foreach (['seats', 'horsepower[gt]', 'horsepower[gte]', 'horsepower[lt]', 'horsepower[lte]', 'horsepower[ne]', 'cargoVolume'] as $expectedName) {
            $this->assertContains($expectedName, $parameterNames, \sprintf('Expected parameter "%s" in OpenAPI documentation', $expectedName));
        }
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $car4 = new SubtypeFilterCar();
        $car4->name = 'car-4';
        $car4->seats = 4;
        $car4->horsepower = 100;
        $manager->persist($car4);

        $car7 = new SubtypeFilterCar();
        $car7->name = 'car-7';
        $car7->seats = 7;
        $car7->horsepower = 150;
        $manager->persist($car7);

        $sports = new SubtypeFilterSportsCar();
        $sports->name = 'sports';
        $sports->seats = 2;
        $sports->horsepower = 400;
        $sports->topSpeed = 300;
        $manager->persist($sports);

        $truck = new SubtypeFilterTruck();
        $truck->name = 'truck';
        $truck->horsepower = 300;
        $truck->cargoVolume = 40;
        $manager->persist($truck);

        $bike = new SubtypeFilterBicycle();
        $bike->name = 'bike';
        $bike->gears = 21;
        $manager->persist($bike);

        $cargoBike = new SubtypeFilterBicycle();
        $cargoBike->name = 'cargo-bike';
        $cargoBike->gears = 7;
        $cargoBike->cargoVolume = 1;
        $manager->persist($cargoBike);

        $manager->flush();
    }
}
