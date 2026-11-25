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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\SubtypeResolver;
use ApiPlatform\Metadata\Tests\Fixtures\SubtypeResolver\Other\SubtypeResolverCar as OtherCar;
use ApiPlatform\Metadata\Tests\Fixtures\SubtypeResolver\Other\SubtypeResolverTruck as OtherTruck;
use ApiPlatform\Metadata\Tests\Fixtures\SubtypeResolver\SubtypeResolverGarage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

class SubtypeResolverTest extends TestCase
{
    public function testSubtypes(): void
    {
        $resolver = $this->createResolver();

        $this->assertSame(['car' => SubtypeResolverCar::class, 'truck' => SubtypeResolverTruck::class, 'bus' => SubtypeResolverBus::class], $resolver->getDirectSubtypes(SubtypeResolverVehicle::class));
        $this->assertSame([SubtypeResolverCar::class, SubtypeResolverTruck::class, SubtypeResolverHeavyTruck::class, SubtypeResolverBus::class], $resolver->getSubtypes(SubtypeResolverVehicle::class));
        // A map listing the class itself or an ancestor does not loop
        $this->assertSame([SubtypeResolverHeavyTruck::class], $resolver->getSubtypes(SubtypeResolverTruck::class));
    }

    public function testTypeNames(): void
    {
        $resolver = $this->createResolver(['car' => OtherCar::class]);

        $this->assertSame('Vehicle', $resolver->getTypeName(SubtypeResolverVehicle::class));
        $this->assertSame('Coach', $resolver->getTypeName(SubtypeResolverBus::class));
        $this->assertSame('SubtypeResolverCar', $resolver->getTypeName(SubtypeResolverCar::class));
        $this->assertSame('SubtypeResolverHeavyTruck', $resolver->getTypeName(SubtypeResolverHeavyTruck::class));
        // Named explicitly, its class short name is also the one of SubtypeResolverCar
        $this->assertSame('GarageCar', $resolver->getTypeName(OtherCar::class));
    }

    /**
     * @return iterable<string, array{array<string, class-string>, string, class-string, class-string}>
     */
    public static function typeNameCollisionProvider(): iterable
    {
        yield 'named like a resource' => [[], 'SubtypeResolverHeavyTruck', SubtypeResolverHeavyTruck::class, SubtypeResolverGarage::class];
        yield 'named like another subtype' => [['truck' => OtherTruck::class], 'Garage', OtherTruck::class, SubtypeResolverTruck::class];
    }

    /**
     * @param array<string, class-string> $garageMapping
     */
    #[DataProvider('typeNameCollisionProvider')]
    public function testTypeNameCollisionThrows(array $garageMapping, string $garageShortName, string $subtype, string $collidingClass): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('"%s" and "%s"', $subtype, $collidingClass));

        $this->createResolver($garageMapping, $garageShortName)->getTypeName(SubtypeResolverCar::class);
    }

    /**
     * @param array<string, class-string> $garageMapping
     */
    private function createResolver(array $garageMapping = [], string $garageShortName = 'Garage'): SubtypeResolver
    {
        $mappings = [
            SubtypeResolverVehicle::class => new ClassDiscriminatorMapping('kind', ['car' => SubtypeResolverCar::class, 'truck' => SubtypeResolverTruck::class, 'bus' => SubtypeResolverBus::class]),
            SubtypeResolverTruck::class => new ClassDiscriminatorMapping('size', ['vehicle' => SubtypeResolverVehicle::class, 'truck' => SubtypeResolverTruck::class, 'heavy' => SubtypeResolverHeavyTruck::class]),
            SubtypeResolverGarage::class => $garageMapping ? new ClassDiscriminatorMapping('kind', $garageMapping) : null,
        ];
        $classDiscriminatorResolver = $this->createStub(ClassDiscriminatorResolverInterface::class);
        $classDiscriminatorResolver->method('getMappingForClass')->willReturnCallback(static fn (string $class): ?ClassDiscriminatorMapping => $mappings[$class] ?? null);

        $resourceNameCollectionFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([SubtypeResolverVehicle::class, SubtypeResolverBus::class, SubtypeResolverGarage::class]));

        $shortNames = [SubtypeResolverVehicle::class => 'Vehicle', SubtypeResolverBus::class => 'Coach', SubtypeResolverGarage::class => $garageShortName];
        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturnCallback(static fn (string $class): ResourceMetadataCollection => new ResourceMetadataCollection($class, [(new ApiResource())->withShortName($shortNames[$class])]));

        return new SubtypeResolver($classDiscriminatorResolver, $resourceNameCollectionFactory, $resourceMetadataCollectionFactory);
    }
}

class SubtypeResolverVehicle
{
}

class SubtypeResolverCar extends SubtypeResolverVehicle
{
}

class SubtypeResolverTruck extends SubtypeResolverVehicle
{
}

class SubtypeResolverHeavyTruck extends SubtypeResolverTruck
{
}

class SubtypeResolverBus extends SubtypeResolverVehicle
{
}
