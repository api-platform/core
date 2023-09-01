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

namespace ApiPlatform\Metadata\Tests\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyCar;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\RelatedDummy;
use ApiPlatform\Tests\Fixtures\DummyIgnoreProperty;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\AttributeMetadata as SerializerAttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadata as SerializerClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;

class SerializerPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public static function groupsProvider(): array
    {
        return [
            [['dummy_read'], ['dummy_write']],
            ['dummy_read', 'dummy_write'],
        ];
    }

    /**
     * @dataProvider groupsProvider
     */
    public function testCreate($readGroups, $writeGroups): void
    {
        $serializerClassMetadataFactoryProphecy = $this->prophesize(SerializerClassMetadataFactoryInterface::class);
        $dummySerializerClassMetadata = new SerializerClassMetadata(Dummy::class);
        $fooSerializerAttributeMetadata = new SerializerAttributeMetadata('foo');
        $fooSerializerAttributeMetadata->addGroup('dummy_read');
        $fooSerializerAttributeMetadata->addGroup('dummy_write');
        $dummySerializerClassMetadata->addAttributeMetadata($fooSerializerAttributeMetadata);
        $relatedDummySerializerAttributeMetadata = new SerializerAttributeMetadata('relatedDummy');
        $relatedDummySerializerAttributeMetadata->addGroup('dummy_read');
        $relatedDummySerializerAttributeMetadata->addGroup('dummy_write');
        $dummySerializerClassMetadata->addAttributeMetadata($relatedDummySerializerAttributeMetadata);
        $nameConvertedSerializerAttributeMetadata = new SerializerAttributeMetadata('nameConverted');
        $dummySerializerClassMetadata->addAttributeMetadata($nameConvertedSerializerAttributeMetadata);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(Dummy::class)->willReturn($dummySerializerClassMetadata);
        $relatedDummySerializerClassMetadata = new SerializerClassMetadata(RelatedDummy::class);
        $nameSerializerAttributeMetadata = new SerializerAttributeMetadata('name');
        $nameSerializerAttributeMetadata->addGroup('dummy_read');
        $relatedDummySerializerClassMetadata->addAttributeMetadata($nameSerializerAttributeMetadata);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(RelatedDummy::class)->willReturn($relatedDummySerializerClassMetadata);
        $dummyCarSerializerClassMetadata = new SerializerClassMetadata(DummyCar::class);
        $nameSerializerAttributeMetadata = new SerializerAttributeMetadata('name');
        $nameSerializerAttributeMetadata->addGroup('dummy_car_read');
        $nameSerializerAttributeMetadata->addGroup('dummy_write');
        $dummyCarSerializerClassMetadata->addAttributeMetadata($nameSerializerAttributeMetadata);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(DummyCar::class)->willReturn($dummyCarSerializerClassMetadata);

        $context = ['normalization_groups' => $readGroups, 'denormalization_groups' => $writeGroups];

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $fooPropertyMetadata = (new ApiProperty())
            ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_ARRAY, true)])
            ->withReadable(false)
            ->withWritable(true);
        $decoratedProphecy->create(Dummy::class, 'foo', $context)->willReturn($fooPropertyMetadata);
        $relatedDummyPropertyMetadata = (new ApiProperty())
            ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, true, RelatedDummy::class)]);
        $decoratedProphecy->create(Dummy::class, 'relatedDummy', $context)->willReturn($relatedDummyPropertyMetadata);
        $nameConvertedPropertyMetadata = (new ApiProperty())
            ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING, true)]);
        $decoratedProphecy->create(Dummy::class, 'nameConverted', $context)->willReturn($nameConvertedPropertyMetadata);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);

        $serializerPropertyMetadataFactory = new SerializerPropertyMetadataFactory($serializerClassMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal(), $resourceClassResolverProphecy->reveal());

        $actual = [];
        $actual[] = $serializerPropertyMetadataFactory->create(Dummy::class, 'foo', $context);
        $actual[] = $serializerPropertyMetadataFactory->create(Dummy::class, 'relatedDummy', $context);
        $actual[] = $serializerPropertyMetadataFactory->create(Dummy::class, 'nameConverted', $context);

        $this->assertInstanceOf(ApiProperty::class, $actual[0]);
        $this->assertFalse($actual[0]->isReadable());
        $this->assertTrue($actual[0]->isWritable());

        $this->assertInstanceOf(ApiProperty::class, $actual[1]);
        $this->assertTrue($actual[1]->isReadable());
        $this->assertTrue($actual[1]->isWritable());
        $this->assertTrue($actual[1]->isReadableLink());
        $this->assertFalse($actual[1]->isWritableLink());

        $this->assertInstanceOf(ApiProperty::class, $actual[2]);
        $this->assertFalse($actual[2]->isReadable());
        $this->assertFalse($actual[2]->isWritable());
    }

    public function testCreateWithIgnoredProperty(): void
    {
        $ignoredSerializerAttributeMetadata = new SerializerAttributeMetadata('ignored');
        $ignoredSerializerAttributeMetadata->addGroup('dummy');
        $ignoredSerializerAttributeMetadata->addGroup('dummy');
        $ignoredSerializerAttributeMetadata->setIgnore(true);

        $dummyIgnorePropertySerializerClassMetadata = new SerializerClassMetadata(DummyIgnoreProperty::class);
        $dummyIgnorePropertySerializerClassMetadata->addAttributeMetadata($ignoredSerializerAttributeMetadata);

        $serializerClassMetadataFactoryProphecy = $this->prophesize(SerializerClassMetadataFactoryInterface::class);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(DummyIgnoreProperty::class)->willReturn($dummyIgnorePropertySerializerClassMetadata);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(DummyIgnoreProperty::class)->willReturn(true);

        $ignoredPropertyMetadata = (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING, true)]);

        $options = [
            'normalization_groups' => ['dummy'],
            'denormalization_groups' => ['dummy'],
        ];

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyIgnoreProperty::class, 'ignored', $options)->willReturn($ignoredPropertyMetadata);

        $serializerPropertyMetadataFactory = new SerializerPropertyMetadataFactory(
            $serializerClassMetadataFactoryProphecy->reveal(),
            $decoratedProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $result = $serializerPropertyMetadataFactory->create(DummyIgnoreProperty::class, 'ignored', $options);

        self::assertFalse($result->isReadable());
        self::assertFalse($result->isWritable());
    }
}
