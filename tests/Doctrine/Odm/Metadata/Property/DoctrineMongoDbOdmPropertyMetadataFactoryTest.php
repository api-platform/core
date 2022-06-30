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

namespace ApiPlatform\Tests\Doctrine\Odm\Metadata\Property;

use ApiPlatform\Doctrine\Odm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineMongoDbOdmPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateNoManager(): void
    {
        $propertyMetadata = new ApiProperty();
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn(null);

        $doctrineMongoDbOdmPropertyMetadataFactory = new DoctrineMongoDbOdmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertEquals($doctrineMongoDbOdmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsIdentifier(): void
    {
        $propertyMetadata = new ApiProperty();
        $propertyMetadata = $propertyMetadata->withIdentifier(true);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadata::class);

        $objectManager = $this->prophesize(DocumentManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldNotBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldNotBeCalled()->willReturn($objectManager->reveal());

        $doctrineMongoDbOdmPropertyMetadataFactory = new DoctrineMongoDbOdmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $this->assertEquals($doctrineMongoDbOdmPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateIsWritable(): void
    {
        $propertyMetadata = new ApiProperty();
        $propertyMetadata = $propertyMetadata->withWritable(false);

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);

        $objectManager = $this->prophesize(DocumentManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineMongoDbOdmPropertyMetadataFactory = new DoctrineMongoDbOdmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineMongoDbOdmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), false);
    }

    public function testCreateClassMetadata(): void
    {
        $propertyMetadata = new ApiProperty();

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getIdentifier()->shouldBeCalled()->willReturn(['id']);

        $objectManager = $this->prophesize(DocumentManager::class);
        $objectManager->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManager->reveal());

        $doctrineMongoDbOdmPropertyMetadataFactory = new DoctrineMongoDbOdmPropertyMetadataFactory($managerRegistry->reveal(), $propertyMetadataFactory->reveal());

        $doctrinePropertyMetadata = $doctrineMongoDbOdmPropertyMetadataFactory->create(Dummy::class, 'id');

        $this->assertEquals($doctrinePropertyMetadata->isIdentifier(), true);
        $this->assertEquals($doctrinePropertyMetadata->isWritable(), false);
    }
}
