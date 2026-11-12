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

namespace ApiPlatform\Doctrine\Orm\Tests\Metadata\Property;

use ApiPlatform\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\DummyPropertyWithDefaultValue;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class DoctrineOrmPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateNoManager(): void
    {
        $propertyMetadata = new ApiProperty();
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn(null);

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertSame($doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsIdentifier(): void
    {
        $propertyMetadata = new ApiProperty();
        $propertyMetadata = $propertyMetadata->withIdentifier(true);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadata::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldNotBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldNotBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertSame($doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsWritable(): void
    {
        $propertyMetadata = new ApiProperty();
        $propertyMetadata = $propertyMetadata->withWritable(false);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ORMClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->getFieldNames()->shouldBeCalled()->willReturn([]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertSame($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertSame($doctrinePropertyMetadata->isWritable(), false);
    }

    public function testCreateWithDefaultOption(): void
    {
        $propertyMetadata = new ApiProperty();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(DummyPropertyWithDefaultValue::class, 'dummyDefaultOption', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ORMClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->getFieldNames()->shouldBeCalled()->willReturn(['dummyDefaultOption']);
        if (class_exists(FieldMapping::class)) {
            $fieldMapping = new FieldMapping('string', 'dummyDefaultOption', 'dummyDefaultOption');
            // TODO: FieldMapping::$default is removed in doctrine/orm 4.0, use $fieldMapping->options = ['default' => 'default value'] instead
            $fieldMapping->default = 'default value';
            $classMetadata->fieldMappings = [$fieldMapping];
            $classMetadata->getFieldMapping('dummyDefaultOption')->willReturn($fieldMapping);
        } else {
            $classMetadata->getFieldMapping('dummyDefaultOption')->willReturn(['options' => ['default' => 'default value']]);
        }

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(DummyPropertyWithDefaultValue::class)->shouldBeCalled()->willReturn($classMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(DummyPropertyWithDefaultValue::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(DummyPropertyWithDefaultValue::class, 'dummyDefaultOption');

        $this->assertSame($doctrinePropertyMetadata->getDefault(), 'default value');
    }

    public function testCreateWithDefaultOptionFromColumnAttribute(): void
    {
        $classMetadata = new ORMClassMetadata(DummyPropertyWithDefaultValue::class);
        $classMetadata->initializeReflection(new RuntimeReflectionService());
        (new AttributeDriver([]))->loadMetadataForClass(DummyPropertyWithDefaultValue::class, $classMetadata);

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturn(new ApiProperty());

        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('getClassMetadata')->willReturn($classMetadata);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($objectManager);

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry, $propertyMetadataFactory);

        $this->assertSame('default value', $doctrineOrmPropertyMetadataFactory->create(DummyPropertyWithDefaultValue::class, 'dummyDefaultOption')->getDefault());
    }

    public function testCreateClassMetadataInfo(): void
    {
        $propertyMetadata = new ApiProperty();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ORMClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->isIdentifierNatural()->shouldBeCalled()->willReturn(true);
        $classMetadata->getFieldNames()->shouldBeCalled()->willReturn([]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertSame($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertSame($doctrinePropertyMetadata->isWritable(), true);
    }

    public function testCreateClassMetadata(): void
    {
        $propertyMetadata = new ApiProperty();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertSame($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertSame($doctrinePropertyMetadata->isWritable(), false);
    }
}
