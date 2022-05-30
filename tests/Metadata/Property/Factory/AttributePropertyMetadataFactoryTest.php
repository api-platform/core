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

namespace ApiPlatform\Tests\Metadata\Property\Factory;

use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\AttributePropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyPhp8ApiPropertyAttribute;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributePropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @requires PHP 8.0
     */
    public function testCreateAttribute()
    {
        $factory = new AttributePropertyMetadataFactory();

        $metadata = $factory->create(DummyPhp8ApiPropertyAttribute::class, 'id');
        $this->assertTrue($metadata->isIdentifier());
        $this->assertSame('the identifier', $metadata->getDescription());

        $metadata = $factory->create(DummyPhp8ApiPropertyAttribute::class, 'foo');
        $this->assertSame('a foo', $metadata->getDescription());
    }

    public function testClassNotFound()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "foo" of class "\\DoNotExist" not found.');

        $factory = new AttributePropertyMetadataFactory();
        $factory->create('\DoNotExist', 'foo');
    }

    public function testClassNotFoundButParentFound()
    {
        $propertyMetadata = new ApiProperty();

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedProphecy->create('\DoNotExist', 'foo', [])->willReturn($propertyMetadata);

        $factory = new AttributePropertyMetadataFactory($decoratedProphecy->reveal());
        $this->assertEquals($propertyMetadata, $factory->create('\DoNotExist', 'foo'));
    }

    /**
     * @requires PHP 8.0
     */
    public function testClassFoundAndParentFound()
    {
        $parentPropertyMetadata = (new ApiProperty('Desc', true, false, true, false, true, false, 'Default', 'Example'))->withTypes(['https://example.com']);

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyPhp8ApiPropertyAttribute::class, 'empty', [])->willReturn($parentPropertyMetadata);

        $factory = new AttributePropertyMetadataFactory($decoratedProphecy->reveal());
        $metadata = $factory->create(DummyPhp8ApiPropertyAttribute::class, 'empty');

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
        // TODO need https://github.com/api-platform/core/pull/4422
        // $this->assertSame(['https://example.com'], $metadata->getTypes());
    }
}
