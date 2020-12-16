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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\PropertyInfo\Metadata\Property;

use ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Core\Bridge\Symfony\Serializer\Metadata\Property\SerializerIgnoredPropertyNameCollectionFactory;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithSerializerIgnoredProperty;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class SerializerIgnorePropertyNameCollectionFactoryTest extends TestCase
{
    public function testCreateMethodReturnsPropertyNameCollectionWithoutIgnoredProperties()
    {
        $usernameAttributeMetadata = $this->prophesize(AttributeMetadataInterface::class);
        $usernameAttributeMetadata->isIgnored()->shouldBeCalled()->willReturn(false);

        $passwordAttributeMetadata = $this->prophesize(AttributeMetadataInterface::class);
        $passwordAttributeMetadata->isIgnored()->shouldBeCalled()->willReturn(true);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getAttributesMetadata()->shouldBeCalled()->willReturn([
            'username' => $usernameAttributeMetadata->reveal(),
            'password' => $passwordAttributeMetadata->reveal()
        ]);

        $classMetadataFactoryProphecy = $this->prophesize(ClassMetadataFactoryInterface::class);
        $classMetadataFactoryProphecy->getMetadataFor(DummyObjectWithSerializerIgnoredProperty::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $factory = new SerializerIgnoredPropertyNameCollectionFactory(
            $classMetadataFactoryProphecy->reveal(),
            new PropertyInfoPropertyNameCollectionFactory(
                new PropertyInfoExtractor([
                    new ReflectionExtractor(),
                ])
            )
        );

        $collection = $factory->create(DummyObjectWithSerializerIgnoredProperty::class);

        self::assertCount(1, $collection->getIterator());
        self::assertContains('username', $collection->getIterator());
        self::assertNotContains('password', $collection->getIterator());
    }
}
