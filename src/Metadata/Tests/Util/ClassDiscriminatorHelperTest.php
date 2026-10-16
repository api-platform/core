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

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Tests\Fixtures\Discriminator\Car;
use ApiPlatform\Metadata\Tests\Fixtures\Discriminator\Hatchback;
use ApiPlatform\Metadata\Tests\Fixtures\Discriminator\SportsCar;
use ApiPlatform\Metadata\Tests\Fixtures\Discriminator\Truck;
use ApiPlatform\Metadata\Tests\Fixtures\Discriminator\Vehicle;
use ApiPlatform\Metadata\Util\ClassDiscriminatorHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

final class ClassDiscriminatorHelperTest extends TestCase
{
    private ClassDiscriminatorFromClassMetadata $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ClassDiscriminatorFromClassMetadata(new ClassMetadataFactory(new AttributeLoader()));
    }

    public function testGetDirectSubtypesOnlyReturnsStrictSubclasses(): void
    {
        $mapping = new ClassDiscriminatorMapping('body', ['car' => Car::class, 'sports' => SportsCar::class, 'vehicle' => Vehicle::class, 'truck' => Truck::class]);

        $this->assertSame(['sports' => SportsCar::class], ClassDiscriminatorHelper::getDirectSubtypes($mapping, Car::class));
        $this->assertSame(['car'], ClassDiscriminatorHelper::getOwnTypes($mapping, Car::class));
    }

    public function testGetSubtypesWalksNestedMaps(): void
    {
        $this->assertEqualsCanonicalizing([Car::class, SportsCar::class, Truck::class], ClassDiscriminatorHelper::getSubtypes($this->resolver, Vehicle::class));
        $this->assertSame([SportsCar::class], ClassDiscriminatorHelper::getSubtypes($this->resolver, Car::class));
    }

    public function testResolveTerminatesWhenANestedMapListsAnAncestor(): void
    {
        $resolved = ClassDiscriminatorHelper::resolve($this->resolver, Vehicle::class, Hatchback::class);

        $this->assertNotNull($resolved);
        $this->assertSame(Car::class, $resolved['class']);
        $this->assertSame(['kind', 'body'], $resolved['type_properties']);
    }

    public function testResolveMostSpecificNestedSubtype(): void
    {
        $resolved = ClassDiscriminatorHelper::resolve($this->resolver, Vehicle::class, SportsCar::class);

        $this->assertNotNull($resolved);
        $this->assertSame(SportsCar::class, $resolved['class']);
        $this->assertSame(['kind', 'body'], $resolved['type_properties']);
    }

    public function testResolveClassDeclaredInItsOwnMap(): void
    {
        $resolved = ClassDiscriminatorHelper::resolve($this->resolver, Car::class, Car::class);

        $this->assertNotNull($resolved);
        $this->assertSame(Car::class, $resolved['class']);
        $this->assertSame(['body'], $resolved['type_properties']);
    }

    public function testResolveReturnsNullForAClassOutsideOfTheMaps(): void
    {
        $this->assertNull(ClassDiscriminatorHelper::resolve($this->resolver, Vehicle::class, Vehicle::class));
        $this->assertNull(ClassDiscriminatorHelper::resolve($this->resolver, Truck::class, Vehicle::class));
        $this->assertNull(ClassDiscriminatorHelper::resolve($this->resolver, \stdClass::class, \stdClass::class));
    }
}
