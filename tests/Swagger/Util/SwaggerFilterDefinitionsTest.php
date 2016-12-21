<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Util;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Util\SwaggerFilterDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerTypeResolver;
use ApiPlatform\Core\Tests\Fixtures\DummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

class SwaggerFilterDefinitionsTest extends \PHPUnit_Framework_TestCase
{
    public function testSwaggerFilerDefinitions()
    {
        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            null,
            [],
            ['get' => ['method' => 'GET', 'filters' => ['f1', 'f2']]],
            []
        );

        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET', 'filters' => ['f1', 'f2']],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $typeResolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $filters = new FilterCollection([
            'f1' => new DummyFilter(['name' => [
                'property' => 'name',
                'type' => 'string',
                'required' => true,
                'strategy' => 'exact',
                'swagger' => ['x-foo' => 'bar'],
            ]]),
            'f2' => new DummyFilter(
                [
                    'ha' => [
                        'property' => 'foo',
                        'type' => 'int',
                        'required' => false,
                        'strategy' => 'partial',
                    ],
                    'ha2' => [
                        'property' => 'foo2',
                        'type' => 'int',
                        'required' => false,
                        'strategy' => 'partial',
                    ],
                ]),
            'f3' => new DummyFilter(['dummy' => [
                'property' => 'dummy',
                'type' => 'int',
                'required' => false,
                'strategy' => 'partial',
            ]]),
        ]);

        $swaggerFilterDefinitions = new SwaggerFilterDefinitions(
            $resourceMetadataFactoryProphecy->reveal(),
            $typeResolver,
            $filters
        );

        $expected = [
            [
                'x-foo' => 'bar',
                'name' => 'name',
                'in' => 'query',
                'required' => true,
                'type' => 'string',
            ],
            [
                'name' => 'ha',
                'in' => 'query',
                'required' => false,
                'type' => 'integer',
            ],
            [
                'name' => 'ha2',
                'in' => 'query',
                'required' => false,
                'type' => 'integer',
            ],
        ];

        $this->assertEquals($expected, $swaggerFilterDefinitions->get($operationData));
    }
}
