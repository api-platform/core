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

namespace ApiPlatform\Core\Tests\JsonApi\Serializer;

use ApiPlatform\Core\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ReservedAttributeNameConverterTest extends \PHPUnit_Framework_TestCase
{
    private $reservedAttributeNameConverter;

    protected function setUp()
    {
        $this->reservedAttributeNameConverter = new ReservedAttributeNameConverter(new CustomConverter());
    }

    public function propertiesProvider()
    {
        return [
            ['id', '_id'],
            ['type', '_type'],
            ['links', '_links'],
            ['relationships', '_relationships'],
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
     *
     * @param string $propertyName
     * @param string $expectedPropertyName
     */
    public function testNormalize($propertyName, $expectedPropertyName)
    {
        $this->assertEquals($expectedPropertyName, $this->reservedAttributeNameConverter->normalize($propertyName));
    }

    /**
     * @dataProvider propertiesProvider
     *
     * @param string $expectedPropertyName
     * @param string $propertyName
     */
    public function testDenormalize($expectedPropertyName, $propertyName)
    {
        $this->assertEquals($expectedPropertyName, $this->reservedAttributeNameConverter->denormalize($propertyName));
    }
}
