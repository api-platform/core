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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\OperationResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OperationResourceMetadataFactoryTest extends TestCase
{
    /**
     * @dataProvider getMetadata
     */
    public function testCreateOperation(ResourceMetadata $before, ResourceMetadata $after, array $formats = [])
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($before);

        $this->assertEquals($after, (new OperationResourceMetadataFactory($decoratedProphecy->reveal(), $formats))->create(Dummy::class));
    }

    public function getMetadata()
    {
        $jsonapi = ['jsonapi' => ['application/vnd.api+json']];

        return [
            // Item operations
            [new ResourceMetadata(null, null, null, null, []), new ResourceMetadata(null, null, null, ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT'], 'delete' => ['method' => 'DELETE']], [])],
            [new ResourceMetadata(null, null, null, null, []), new ResourceMetadata(null, null, null, ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT'], 'patch' => ['method' => 'PATCH'], 'delete' => ['method' => 'DELETE']], []), $jsonapi],
            [new ResourceMetadata(null, null, null, ['get'], []), new ResourceMetadata(null, null, null, ['get' => ['method' => 'GET']], [])],
            [new ResourceMetadata(null, null, null, ['put'], []), new ResourceMetadata(null, null, null, ['put' => ['method' => 'PUT']], [])],
            [new ResourceMetadata(null, null, null, ['delete'], []), new ResourceMetadata(null, null, null, ['delete' => ['method' => 'DELETE']], [])],
            [new ResourceMetadata(null, null, null, ['patch'], []), new ResourceMetadata(null, null, null, ['patch' => []], [])],
            [new ResourceMetadata(null, null, null, ['patch'], []), new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH']], []), $jsonapi],

            // Collection operations
            [new ResourceMetadata(null, null, null, []), new ResourceMetadata(null, null, null, [], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']])],
            [new ResourceMetadata(null, null, null, [], ['get']), new ResourceMetadata(null, null, null, [], ['get' => ['method' => 'GET']])],
            [new ResourceMetadata(null, null, null, [], ['post']), new ResourceMetadata(null, null, null, [], ['post' => ['method' => 'POST']])],
            [new ResourceMetadata(null, null, null, [], ['options']), new ResourceMetadata(null, null, null, [], ['options' => []])],
        ];
    }
}
