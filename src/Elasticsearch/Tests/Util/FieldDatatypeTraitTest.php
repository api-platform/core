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

namespace ApiPlatform\Elasticsearch\Tests\Util;

use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Elasticsearch\Util\FieldDatatypeTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\TypeInfo\Type;

class FieldDatatypeTraitTest extends TestCase
{
    use ProphecyTrait;

    public function testGetNestedFieldPath(): void
    {
        $fieldDatatype = $this->getValidFieldDatatype();

        self::assertSame('foo.bar', $fieldDatatype->getNestedFieldPath(Foo::class, 'foo.bar.baz'));
        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'foo.baz'));
        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'baz'));
    }

    public function testGetNestedFieldInNestedCollection(): void
    {
        $fieldDatatype = $this->getValidFieldDatatype();

        self::assertSame('foo.foo.bar', $fieldDatatype->getNestedFieldPath(Foo::class, 'foo.foo.bar.baz'));
        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'foo.foo.baz'));
        self::assertSame('foo.bar', $fieldDatatype->getNestedFieldPath(Foo::class, 'foo.bar.foo.baz'));
        self::assertSame('bar', $fieldDatatype->getNestedFieldPath(Foo::class, 'bar.foo'));
        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'baz'));
    }

    public function testGetNestedFieldPathWithPropertyNotFound(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willThrow(new PropertyNotFoundException())->shouldBeCalled();

        $fieldDatatype = self::createFieldDatatypeInstance($propertyMetadataFactoryProphecy->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal());

        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'foo.bar'));
    }

    public function testGetNestedFieldPathWithPropertyWithoutType(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willReturn(new ApiProperty())->shouldBeCalled();

        $fieldDatatype = self::createFieldDatatypeInstance($propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal());

        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'foo.bar'));
    }

    public function testGetNestedFieldPathWithInvalidCollectionType(): void
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willReturn((new ApiProperty())->withNativeType(Type::string()))->shouldBeCalled();

        $fieldDatatype = self::createFieldDatatypeInstance($propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal());

        self::assertNull($fieldDatatype->getNestedFieldPath(Foo::class, 'foo.bar'));
    }

    public function testIsNestedField(): void
    {
        $fieldDatatype = $this->getValidFieldDatatype();

        self::assertTrue($fieldDatatype->isNestedField(Foo::class, 'foo.bar.baz'));
        self::assertFalse($fieldDatatype->isNestedField(Foo::class, 'foo.baz'));
        self::assertFalse($fieldDatatype->isNestedField(Foo::class, 'baz'));
    }

    private function getValidFieldDatatype()
    {
        $fooType = Type::object(Foo::class);
        $barType = Type::list(Type::object(Foo::class));
        $bazType = Type::string();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willReturn((new ApiProperty())->withNativeType($fooType))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'bar')->willReturn((new ApiProperty())->withNativeType($barType))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'baz')->willReturn((new ApiProperty())->withNativeType($bazType));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Foo::class)->willReturn(true)->shouldBeCalled();

        return self::createFieldDatatypeInstance($propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal());
    }

    private static function createFieldDatatypeInstance(PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver)
    {
        return new class($propertyMetadataFactory, $resourceClassResolver) {
            use FieldDatatypeTrait {
                isNestedField as public;
                getNestedFieldPath as public;
            }

            public function __construct(PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver)
            {
                $this->propertyMetadataFactory = $propertyMetadataFactory;
                $this->resourceClassResolver = $resourceClassResolver;
            }
        };
    }
}
