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

namespace ApiPlatform\Core\Tests\Serializer\Filter;

use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class PropertyFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testApply()
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithOverriding()
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true);
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithoutPropertiesInRequest()
    {
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    public function testApplyWithInvalidPropertiesInRequest()
    {
        $request = new Request(['properties' => 'foo,bar,baz']);
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    public function testGetDescription()
    {
        $propertyFilter = new PropertyFilter('custom_properties');
        $expectedDescription = [
            'custom_properties[]' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
            ],
        ];

        $this->assertEquals($expectedDescription, $propertyFilter->getDescription(DummyProperty::class));
    }
}
