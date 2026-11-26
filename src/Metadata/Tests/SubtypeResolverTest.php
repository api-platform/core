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
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\SubtypeResolver;
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
        $resolver = $this->createResolver();

        $this->assertSame('Vehicle', $resolver->getTypeName(SubtypeResolverVehicle::class));
        $this->assertSame(['Vehicle'], $resolver->getTypes(SubtypeResolverVehicle::class));
        $this->assertSame('Coach', $resolver->getTypeName(SubtypeResolverBus::class));
        $this->assertSame(['https://schema.org/BusOrCoach'], $resolver->getTypes(SubtypeResolverBus::class));
        $this->assertSame('SubtypeResolverCar', $resolver->getTypeName(SubtypeResolverCar::class));
        $this->assertSame(['SubtypeResolverCar'], $resolver->getTypes(SubtypeResolverCar::class));
    }

    private function createResolver(): SubtypeResolver
    {
        $mappings = [
            SubtypeResolverVehicle::class => new ClassDiscriminatorMapping('kind', ['car' => SubtypeResolverCar::class, 'truck' => SubtypeResolverTruck::class, 'bus' => SubtypeResolverBus::class]),
            SubtypeResolverTruck::class => new ClassDiscriminatorMapping('size', ['vehicle' => SubtypeResolverVehicle::class, 'truck' => SubtypeResolverTruck::class, 'heavy' => SubtypeResolverHeavyTruck::class]),
        ];
        $classDiscriminatorResolver = $this->createStub(ClassDiscriminatorResolverInterface::class);
        $classDiscriminatorResolver->method('getMappingForClass')->willReturnCallback(static fn (string $class): ?ClassDiscriminatorMapping => $mappings[$class] ?? null);

        $resources = [
            SubtypeResolverVehicle::class => new ApiResource(shortName: 'Vehicle'),
            SubtypeResolverBus::class => new ApiResource(shortName: 'Coach', types: ['https://schema.org/BusOrCoach']),
        ];
        $resourceNameCollectionFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->method('create')->willReturn(new ResourceNameCollection(array_keys($resources)));

        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturnCallback(static fn (string $class): ResourceMetadataCollection => new ResourceMetadataCollection($class, [$resources[$class]]));

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
