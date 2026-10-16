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

namespace ApiPlatform\Doctrine\Common\Tests\Serializer\Mapping\Loader;

use ApiPlatform\Doctrine\Common\Serializer\Mapping\Loader\DoctrineDiscriminatorMappingLoader;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as OdmClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadata;

final class DoctrineDiscriminatorMappingLoaderTest extends TestCase
{
    use ProphecyTrait;

    private const ORM_MAP = [
        'vehicle' => LoaderVehicle::class,
        'car' => LoaderCar::class,
        'sportsCar' => LoaderSportsCar::class,
        'truck' => LoaderTruck::class,
    ];

    public function testRootGetsTheWholeDoctrineMap(): void
    {
        $metadata = new ClassMetadata(LoaderVehicle::class);

        $this->assertTrue($this->createOrmLoader()->loadClassMetadata($metadata));
        $this->assertEquals(new ClassDiscriminatorMapping('kind', self::ORM_MAP), $metadata->getClassDiscriminatorMapping());
    }

    public function testIntermediateClassGetsItselfAndItsSubclasses(): void
    {
        $metadata = new ClassMetadata(LoaderCar::class);

        $this->assertTrue($this->createOrmLoader()->loadClassMetadata($metadata));
        $this->assertEquals(
            new ClassDiscriminatorMapping('kind', ['car' => LoaderCar::class, 'sportsCar' => LoaderSportsCar::class]),
            $metadata->getClassDiscriminatorMapping(),
        );
    }

    public function testLeavesAreLeftAlone(): void
    {
        $loader = $this->createOrmLoader();

        foreach ([LoaderSportsCar::class, LoaderTruck::class] as $class) {
            $metadata = new ClassMetadata($class);
            $this->assertFalse($loader->loadClassMetadata($metadata));
            $this->assertNull($metadata->getClassDiscriminatorMapping());
        }
    }

    public function testExistingSerializerMappingWins(): void
    {
        $mapping = new ClassDiscriminatorMapping('type', ['car' => LoaderCar::class]);
        $metadata = new ClassMetadata(LoaderVehicle::class, $mapping);

        $this->assertFalse($this->createOrmLoader()->loadClassMetadata($metadata));
        $this->assertSame($mapping, $metadata->getClassDiscriminatorMapping());
    }

    public function testSerializerDiscriminatorMapDeclaredInTheHierarchyWins(): void
    {
        $doctrineMetadata = $this->createOrmMetadata(LoaderSerializerMappedChild::class, [
            'base' => LoaderSerializerMappedBase::class,
            'child' => LoaderSerializerMappedChild::class,
            'grandChild' => LoaderSerializerMappedGrandChild::class,
        ]);
        $metadata = new ClassMetadata(LoaderSerializerMappedChild::class);

        $this->assertFalse($this->createLoader([LoaderSerializerMappedChild::class => $doctrineMetadata])->loadClassMetadata($metadata));
        $this->assertNull($metadata->getClassDiscriminatorMapping());
    }

    public function testNonResourceHierarchiesAreLeftAlone(): void
    {
        $metadata = new ClassMetadata(LoaderVehicle::class);
        $loader = $this->createLoader([LoaderVehicle::class => $this->createOrmMetadata(LoaderVehicle::class, self::ORM_MAP)], []);

        $this->assertFalse($loader->loadClassMetadata($metadata));
        $this->assertNull($metadata->getClassDiscriminatorMapping());
    }

    public function testHierarchyIsExposedWhenOnlyASubclassIsAResource(): void
    {
        $metadata = new ClassMetadata(LoaderVehicle::class);
        $loader = $this->createLoader([LoaderVehicle::class => $this->createOrmMetadata(LoaderVehicle::class, self::ORM_MAP)], [LoaderTruck::class]);

        $this->assertTrue($loader->loadClassMetadata($metadata));
        $this->assertEqualsCanonicalizing(self::ORM_MAP, $metadata->getClassDiscriminatorMapping()?->getTypesMapping());
    }

    public function testUnmanagedClassesAreLeftAlone(): void
    {
        $metadata = new ClassMetadata(LoaderVehicle::class);

        $this->assertFalse($this->createLoader([])->loadClassMetadata($metadata));
        $this->assertNull($metadata->getClassDiscriminatorMapping());
    }

    public function testClassesWithoutInheritanceAreLeftAlone(): void
    {
        $metadata = new ClassMetadata(LoaderVehicle::class);
        $doctrineMetadata = new OrmClassMetadata(LoaderVehicle::class);

        $this->assertFalse($this->createLoader([LoaderVehicle::class => $doctrineMetadata])->loadClassMetadata($metadata));
        $this->assertNull($metadata->getClassDiscriminatorMapping());
    }

    public function testIntegerDiscriminatorValuesAreCastToStrings(): void
    {
        $metadata = new ClassMetadata(LoaderVehicle::class);
        $loader = $this->createLoader([LoaderVehicle::class => $this->createOrmMetadata(LoaderVehicle::class, [1 => LoaderCar::class, 2 => LoaderTruck::class])]);

        $this->assertTrue($loader->loadClassMetadata($metadata));
        $this->assertEqualsCanonicalizing(['1' => LoaderCar::class, '2' => LoaderTruck::class], $metadata->getClassDiscriminatorMapping()?->getTypesMapping());
        $this->assertSame(LoaderCar::class, $metadata->getClassDiscriminatorMapping()->getClassForType('1'));
    }

    public function testMongoDbOdmDiscriminatorField(): void
    {
        $doctrineMetadata = new OdmClassMetadata(LoaderVehicle::class);
        $doctrineMetadata->discriminatorField = 'kind';
        $doctrineMetadata->discriminatorMap = ['car' => LoaderCar::class, 'truck' => LoaderTruck::class];
        $metadata = new ClassMetadata(LoaderVehicle::class);

        $this->assertTrue($this->createLoader([LoaderVehicle::class => $doctrineMetadata])->loadClassMetadata($metadata));
        $this->assertEquals(
            new ClassDiscriminatorMapping('kind', ['car' => LoaderCar::class, 'truck' => LoaderTruck::class]),
            $metadata->getClassDiscriminatorMapping(),
        );
    }

    private function createOrmLoader(): DoctrineDiscriminatorMappingLoader
    {
        $metadata = [];
        foreach (self::ORM_MAP as $class) {
            $metadata[$class] = $this->createOrmMetadata($class, self::ORM_MAP);
        }

        return $this->createLoader($metadata);
    }

    /**
     * @param array<array-key, class-string> $discriminatorMap
     */
    private function createOrmMetadata(string $class, array $discriminatorMap): OrmClassMetadata
    {
        $metadata = new OrmClassMetadata($class);
        // ORM 2 stores the discriminator column as an array
        $metadata->discriminatorColumn = class_exists(DiscriminatorColumnMapping::class)
            ? new DiscriminatorColumnMapping('string', 'kind', 'kind')
            : ['type' => 'string', 'fieldName' => 'kind', 'name' => 'kind'];
        $metadata->discriminatorMap = $discriminatorMap;

        return $metadata;
    }

    /**
     * @param array<class-string, object> $doctrineMetadata
     * @param class-string[]|null         $resourceClasses  defaults to the root of every hierarchy
     */
    private function createLoader(array $doctrineMetadata, ?array $resourceClasses = null): DoctrineDiscriminatorMappingLoader
    {
        $resourceClasses ??= [LoaderVehicle::class, LoaderSerializerMappedBase::class];

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::type('string'))->willReturn(null);
        foreach ($doctrineMetadata as $class => $metadata) {
            $manager = $this->prophesize(ObjectManager::class);
            $manager->getClassMetadata($class)->willReturn($metadata);
            $managerRegistry->getManagerForClass($class)->willReturn($manager->reveal());
        }

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(static fn (array $args): bool => \in_array($args[0], $resourceClasses, true));

        return new DoctrineDiscriminatorMappingLoader($managerRegistry->reveal(), $resourceClassResolver->reveal());
    }
}

class LoaderVehicle
{
}

class LoaderCar extends LoaderVehicle
{
}

class LoaderSportsCar extends LoaderCar
{
}

class LoaderTruck extends LoaderVehicle
{
}

#[DiscriminatorMap(typeProperty: 'type', mapping: ['child' => LoaderSerializerMappedChild::class])]
class LoaderSerializerMappedBase
{
}

class LoaderSerializerMappedChild extends LoaderSerializerMappedBase
{
}

class LoaderSerializerMappedGrandChild extends LoaderSerializerMappedChild
{
}
