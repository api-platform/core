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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Util\SwaggerTypeResolver;

class SwaggerTypeResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testTypeResolve()
    {
        $resourceMetadata = new ResourceMetadata('shortName');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('resourceClass')->willReturn($resourceMetadata);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass('noResourceClass')->willReturn(false);
        $resourceClassResolverProphecy->isResourceClass('resourceClass')->willReturn(true);

        $resolver = new SwaggerTypeResolver(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $this->assertEquals(['type' => 'string'], $resolver->resolve('string', false));
        $this->assertEquals(['type' => 'integer'], $resolver->resolve('int', false));
        $this->assertEquals(['type' => 'number'], $resolver->resolve('float', false));
        $this->assertEquals(['type' => 'boolean'], $resolver->resolve('bool', false));
        $this->assertEquals(['type' => 'string'], $resolver->resolve('object', false));
        $this->assertEquals(['type' => 'string'], $resolver->resolve('object', false, 'noResourceClass'));
        $this->assertEquals(
            ['type' => 'string', 'format' => 'date-time'],
            $resolver->resolve('object', false, 'DateTime')
        );
        $this->assertEquals(
            ['$ref' => '#/definitions/shortName'],
            $resolver->resolve('object', false, 'resourceClass', true)
        );
        $this->assertEquals(
            ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'date-time']],
            $resolver->resolve('object', true, 'DateTime', true)
        );
    }
}
