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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Metadata\Property;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyPropertyWithDefaultValue;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class DoctrineOrmPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateNoManager()
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn(null);

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertEquals($doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsIdentifier()
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withIdentifier(true);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldNotBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldNotBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertEquals($doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsWritable()
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withWritable(false);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->getFieldNames()->shouldBeCalled()->willReturn([]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), false);
    }

    public function testCreateWithDefaultOption()
    {
        $propertyMetadata = new PropertyMetadata();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(DummyPropertyWithDefaultValue::class, 'dummyDefaultOption', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = new ClassMetadataInfo(DummyPropertyWithDefaultValue::class);
        $classMetadata->fieldMappings = [
            'dummyDefaultOption' => ['options' => ['default' => 'default value']],
        ];

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(DummyPropertyWithDefaultValue::class)->shouldBeCalled()->willReturn($classMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(DummyPropertyWithDefaultValue::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(DummyPropertyWithDefaultValue::class, 'dummyDefaultOption');

        $this->assertEquals($doctrinePropertyMetadata->getDefault(), 'default value');
    }

    public function testCreateClassMetadataInfo()
    {
        $propertyMetadata = new PropertyMetadata();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);
        $classMetadata->isIdentifierNatural()->shouldBeCalled()->willReturn(true);
        $classMetadata->getFieldNames()->shouldBeCalled()->willReturn([]);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineOrmPropertyMetadataFactory = new DoctrineOrmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineOrmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), true);
    }

    public function testCreateClassMetadata()
    {
        $propertyMetadata = new PropertyMetadata();

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

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), false);
    }
}
