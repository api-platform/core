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

namespace ApiPlatform\Tests\JsonApi\Serializer;

use ApiPlatform\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ReservedAttributeNameConverterTest extends TestCase
{
    private ReservedAttributeNameConverter $reservedAttributeNameConverter;

    protected function setUp(): void
    {
        $this->reservedAttributeNameConverter = new ReservedAttributeNameConverter(new CustomConverter());
    }

    public function propertiesProvider(): array
    {
        return [
            ['id', '_id'],
            ['type', '_type'],
            ['links', '_links'],
            ['relationships', '_relationships'],
            ['included', '_included'],
            ['foo', 'foo'],
            ['bar', 'bar'],
            ['baz', 'baz'],
            ['qux', 'qux'],
            ['nameConverted', 'name_converted'],
            ['_tilleuls', '_tilleuls'],
        ];
    }

    /**
     * @dataProvider propertiesProvider
     */
    public function testNormalize(string $propertyName, string $expectedPropertyName): void
    {
        $this->assertSame($expectedPropertyName, $this->reservedAttributeNameConverter->normalize($propertyName));
    }

    /**
     * @dataProvider propertiesProvider
     */
    public function testDenormalize(string $expectedPropertyName, string $propertyName): void
    {
        $this->assertSame($expectedPropertyName, $this->reservedAttributeNameConverter->denormalize($propertyName));
    }
}
