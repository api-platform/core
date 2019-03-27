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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Metadata\Property\Factory\AnnotationSubresourceMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

class AnnotationSubresourceMetadataFactoryTest extends TestCase
{
    /**
     * @dataProvider dependenciesProvider
     */
    public function testCreateProperty($reader, $decorated)
    {
        $factory = new AnnotationSubresourceMetadataFactory($reader->reveal(), $decorated->reveal());
        $metadata = $factory->create(Dummy::class, 'relatedDummies');

        $this->assertEquals(new SubresourceMetadata(RelatedDummy::class, true, null), $metadata->getSubresource());
    }

    public function dependenciesProvider(): array
    {
        $annotation = new ApiSubresource();

        $propertyReaderProphecy = $this->prophesize(Reader::class);
        $propertyReaderProphecy->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiSubresource::class)->willReturn($annotation)->shouldBeCalled();

        $relatedDummyType = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);
        $subresourceType = new Type(Type::BUILTIN_TYPE_OBJECT, false, ArrayCollection::class, true, new Type(Type::BUILTIN_TYPE_INT), $relatedDummyType);

        $decoratedReturnProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedReturnProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(new PropertyMetadata($subresourceType, 'Several dummies'))->shouldBeCalled();

        return [
            [$propertyReaderProphecy, $decoratedReturnProphecy],
        ];
    }

    public function testCreatePropertyUnknownType()
    {
        $this->expectException(InvalidResourceException::class);
        $this->expectExceptionMessage('Property "relatedDummies" on resource "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy" is declared as a subresource, but its type could not be determined.');

        $annotation = new ApiSubresource();

        $propertyReaderProphecy = $this->prophesize(Reader::class);
        $propertyReaderProphecy->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiSubresource::class)->willReturn($annotation)->shouldBeCalled();

        $decoratedReturnProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedReturnProphecy->create(Dummy::class, 'relatedDummies', [])->willReturn(new PropertyMetadata(null, 'Several dummies'))->shouldBeCalled();

        $factory = new AnnotationSubresourceMetadataFactory($propertyReaderProphecy->reveal(), $decoratedReturnProphecy->reveal());
        $factory->create(Dummy::class, 'relatedDummies');
    }
}
