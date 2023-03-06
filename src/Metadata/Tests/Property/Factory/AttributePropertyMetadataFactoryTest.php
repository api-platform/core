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
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\AttributePropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyEnum;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyWithApiPropertyAttributes;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributePropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateAttribute(): void
    {
        $factory = new AttributePropertyMetadataFactory();

        $metadata = $factory->create(DummyWithApiPropertyAttributes::class, 'id');
        $this->assertTrue($metadata->isIdentifier());
        $this->assertSame('the identifier', $metadata->getDescription());

        $metadata = $factory->create(DummyWithApiPropertyAttributes::class, 'foo');
        $this->assertSame('a foo', $metadata->getDescription());

        $metadata = $factory->create(DummyEnum::class, 'B');
        $this->assertSame('The B.', $metadata->getDescription());
    }

    public function testClassNotFound(): void
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "foo" of class "\\DoNotExist" not found.');

        $factory = new AttributePropertyMetadataFactory();
        $factory->create('\DoNotExist', 'foo');
    }

    public function testClassNotFoundButParentFound(): void
    {
        $propertyMetadata = new ApiProperty();

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedProphecy->create('\DoNotExist', 'foo', [])->willReturn($propertyMetadata);

        $factory = new AttributePropertyMetadataFactory($decoratedProphecy->reveal());
        $this->assertSame($propertyMetadata, $factory->create('\DoNotExist', 'foo'));
    }

    public function testClassFoundAndParentFound(): void
    {
        $parentPropertyMetadata = (new ApiProperty('Desc', true, false, true, false, true, false, 'Default', 'Example'))->withTypes(['https://example.com']);

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyWithApiPropertyAttributes::class, 'empty', [])->willReturn($parentPropertyMetadata);

        $factory = new AttributePropertyMetadataFactory($decoratedProphecy->reveal());
        $metadata = $factory->create(DummyWithApiPropertyAttributes::class, 'empty');

        $this->assertNotSame($parentPropertyMetadata, $metadata);
        $this->assertSame('Desc', $metadata->getDescription());
        $this->assertTrue($metadata->isReadable());
        $this->assertFalse($metadata->isWritable());
        $this->assertTrue($metadata->isReadableLink());
        $this->assertFalse($metadata->isWritableLink());
        $this->assertTrue($metadata->isRequired());
        $this->assertFalse($metadata->isIdentifier());
        $this->assertSame('Default', $metadata->getDefault());
        $this->assertSame('Example', $metadata->getExample());
        $this->assertEquals(['https://example.com'], $metadata->getTypes());
    }
}
