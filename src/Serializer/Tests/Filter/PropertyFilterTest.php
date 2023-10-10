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

namespace ApiPlatform\Serializer\Tests\Filter;

use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DummyProperty;
use ApiPlatform\Serializer\Tests\Fixtures\Serializer\NameConverter\CustomConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class PropertyFilterTest extends TestCase
{
    public function testApply(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithOverriding(): void
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true);
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'baz']], $context);
    }

    public function testApplyWithoutPropertiesInRequest(): void
    {
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    public function testApplyWithPropertiesWhitelist(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['bar', 'fuz', 'foo']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'bar']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndNestedProperty(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'group' => ['baz' => ['qux']]]);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'group' => ['baz' => ['qux']]]], $context);
    }

    public function testApplyWithPropertiesWhitelistNotMatchingAnyProperty(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'group' => ['baz' => ['baz', 'qux'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndOverriding(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'baz']);
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'baz']], $context);
    }

    public function testApplyWithPropertiesInPropertyFilterAttribute(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['properties' => ['fooz']]]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'fooz']], $context);
    }

    public function testApplyWithInvalidPropertiesInRequest(): void
    {
        $request = new Request(['properties' => 'foo,bar,baz']);
        $context = ['attributes' => ['foo', 'bar']];

        $propertyFilter = new PropertyFilter();
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar']], $context);
    }

    public function testApplyWithNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'name_converted', 'foo', 'bar', 'nameConverted']], $context);
    }

    public function testApplyWithOverridingAndNameConverter(): void
    {
        $request = new Request(['custom_properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('custom_properties', true, null, new CustomConverter());
        $propertyFilter->apply($request, false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'bar', 'nameConverted']], $context);
    }

    public function testApplyWithoutPropertiesInRequestAndNameConverter(): void
    {
        $context = ['attributes' => ['foo', 'name_converted']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply(new Request(), false, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'name_converted']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'name_converted', 'baz']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['nameConverted', 'fuz', 'foo'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted']], $context);
    }

    public function testApplyWithPropertiesWhitelistWithNestedPropertyAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['foo' => null, 'nameConverted' => ['baz' => ['nameConverted']]], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux', 'foo', 'nameConverted' => ['baz' => ['nameConverted']]]], $context);
    }

    public function testApplyWithPropertiesWhitelistNotMatchingAnyPropertyAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted' => ['baz' => ['baz', 'name_converted'], 'qux']]]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', false, ['fuz', 'fiz'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['qux']], $context);
    }

    public function testApplyWithPropertiesWhitelistAndOverridingAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'name_converted']]);
        $context = ['attributes' => ['qux']];

        $propertyFilter = new PropertyFilter('properties', true, ['foo', 'nameConverted'], new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'nameConverted']], $context);
    }

    public function testApplyWithPropertiesInPropertyFilterAttributeAndNameConverter(): void
    {
        $request = new Request(['properties' => ['foo', 'bar', 'baz']], [], ['_api_filters' => ['properties' => ['name_converted']]]);
        $context = ['attributes' => ['foo', 'qux']];

        $propertyFilter = new PropertyFilter('properties', false, null, new CustomConverter());
        $propertyFilter->apply($request, true, [], $context);

        $this->assertEquals(['attributes' => ['foo', 'qux', 'nameConverted']], $context);
    }

    public function testGetDescription(): void
    {
        $propertyFilter = new PropertyFilter('custom_properties');
        $expectedDescription = [
            'custom_properties[]' => [
                'property' => null,
                'type' => 'string',
                'is_collection' => true,
                'required' => false,
                'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: custom_properties[]={propertyName}&custom_properties[]={anotherPropertyName}&custom_properties[{nestedPropertyParent}][]={nestedProperty}',
                'swagger' => [
                    'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: custom_properties[]={propertyName}&custom_properties[]={anotherPropertyName}&custom_properties[{nestedPropertyParent}][]={nestedProperty}',
                    'name' => 'custom_properties[]',
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'openapi' => [
                    'description' => 'Allows you to reduce the response to contain only the properties you need. If your desired property is nested, you can address it using nested arrays. Example: custom_properties[]={propertyName}&custom_properties[]={anotherPropertyName}&custom_properties[{nestedPropertyParent}][]={nestedProperty}',
                    'name' => 'custom_properties[]',
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedDescription, $propertyFilter->getDescription(DummyProperty::class));
    }
}
