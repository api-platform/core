<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\AnnotationPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Annotations\Reader;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationPropertyNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getReaders
     */
    public function testCreateProperty(ProphecyInterface $reader)
    {
        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated->create(Dummy::class, 'name', [])->willThrow(new PropertyNotFoundException())->shouldBeCalled();

        $factory = new AnnotationPropertyMetadataFactory($reader->reveal(), $decorated->reveal());
        $metadata = $factory->create(Dummy::class, 'name');

        $this->assertEquals('description', $metadata->getDescription());
        $this->assertTrue($metadata->isReadable());
        $this->assertTrue($metadata->isWritable());
        $this->assertFalse($metadata->isReadableLink());
        $this->assertFalse($metadata->isWritableLink());
        $this->assertFalse($metadata->isIdentifier());
        $this->assertTrue($metadata->isRequired());
        $this->assertEquals('foo', $metadata->getIri());
        $this->assertEquals(['foo' => 'bar'], $metadata->getAttributes());
    }

    public function getReaders()
    {
        $annotation = new ApiProperty();
        $annotation->description = 'description';
        $annotation->readable = true;
        $annotation->writable = true;
        $annotation->readableLink = false;
        $annotation->writableLink = false;
        $annotation->identifier = false;
        $annotation->required = true;
        $annotation->iri = 'foo';
        $annotation->attributes = ['foo' => 'bar'];

        $propertyReader = $this->prophesize(Reader::class);
        $propertyReader->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiProperty::class)->willReturn($annotation)->shouldBeCalled();

        $getterReader = $this->prophesize(Reader::class);
        $getterReader->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();
        $getterReader->getMethodAnnotation(Argument::type(\ReflectionMethod::class), ApiProperty::class)->willReturn($annotation)->shouldBeCalled();

        $setterReader = $this->prophesize(Reader::class);
        $setterReader->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();
        $setterReader->getMethodAnnotation(Argument::type(\ReflectionMethod::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();
        $setterReader->getMethodAnnotation(Argument::type(\ReflectionMethod::class), ApiProperty::class)->willReturn($annotation)->shouldBeCalled();

        return [
            [$propertyReader],
            [$getterReader],
            [$setterReader],
        ];
    }
}
