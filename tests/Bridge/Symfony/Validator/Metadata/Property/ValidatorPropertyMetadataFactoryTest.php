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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\Metadata\Property;

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyValidatedEntity;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ValidatorPropertyMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $validatorClassMetadata;

    public function setUp()
    {
        $this->validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader(new DocParser())))->loadClassMetadata($this->validatorClassMetadata);
    }

    public function testCreateWithPropertyWithRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy');

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithNotRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy date', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate');

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithoutConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy id', true, true, null, null, null, true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyId', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyId');

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithRightValidationGroupsAndRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy group', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['dummy']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['dummy']]);

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithBadValidationGroupsAndRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy group', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['ymmud']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['ymmud']]);

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithNonStringValidationGroupsAndRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy group', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [1312]])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [1312]]);

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithRequiredByDecorated()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy date', true, true, null, null, true, false);
        $expectedPropertyMetadata = clone $propertyMetadata;

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory($validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate');

        $this->assertInstanceOf(PropertyMetadata::class, $resultedPropertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }
}
