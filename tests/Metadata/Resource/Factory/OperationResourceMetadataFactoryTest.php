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
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OperationResourceMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getMetadata
     */
    public function testCreateOperation(ResourceMetadata $before, ResourceMetadata $after, array $formats = []): void
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($before);

        $this->assertEquals($after, (new OperationResourceMetadataFactory($decoratedProphecy->reveal(), $formats))->create(Dummy::class));
    }

    public function getMetadata(): iterable
    {
        $jsonapi = ['jsonapi' => ['application/vnd.api+json']];

        // Item operations
        yield [new ResourceMetadata(null, null, null, null, [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get', 'put', 'delete']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, null, [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get', 'put', 'patch', 'delete']), [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['get'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['put'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['put']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['delete'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['delete']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], [], null, [], []), new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch', 'stateless' => null]], [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], [], null, [], []), new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch', 'stateless' => null]], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['untouched' => ['method' => 'GET']], [], null, [], []), new ResourceMetadata(null, null, null, ['untouched' => ['method' => 'GET', 'stateless' => null]], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['untouched_custom' => ['route_name' => 'custom_route']], [], null, [], []), new ResourceMetadata(null, null, null, ['untouched_custom' => ['route_name' => 'custom_route', 'stateless' => null]], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['stateless_operation' => ['method' => 'GET', 'stateless' => true]], [], null, [], []), new ResourceMetadata(null, null, null, ['stateless_operation' => ['method' => 'GET', 'stateless' => true]], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['statefull_attribute' => ['method' => 'GET']], [], ['stateless' => false], [], []), new ResourceMetadata(null, null, null, ['statefull_attribute' => ['method' => 'GET', 'stateless' => false]], [], ['stateless' => false], [], []), $jsonapi];

        // Collection operations
        yield [new ResourceMetadata(null, null, null, [], null, null, [], []), new ResourceMetadata(null, null, null, [], $this->getOperations(['get', 'post']), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['get'], null, [], []), new ResourceMetadata(null, null, null, [], $this->getOperations(['get']), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['post'], null, [], []), new ResourceMetadata(null, null, null, [], $this->getOperations(['post']), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['options' => ['method' => 'OPTIONS', 'route_name' => 'options']], null, [], []), new ResourceMetadata(null, null, null, [], ['options' => ['route_name' => 'options', 'method' => 'OPTIONS', 'stateless' => null]], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['untouched' => ['method' => 'GET']], null, [], []), new ResourceMetadata(null, null, null, [], ['untouched' => ['method' => 'GET', 'stateless' => null]], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['untouched_custom' => ['route_name' => 'custom_route']], null, [], []), new ResourceMetadata(null, null, null, [], ['untouched_custom' => ['route_name' => 'custom_route', 'stateless' => null]], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['statefull_operation' => ['method' => 'GET', 'stateless' => false]], null, null, []), new ResourceMetadata(null, null, null, [], ['statefull_operation' => ['method' => 'GET', 'stateless' => false]], null, null, []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, [], ['stateless_attribute' => ['method' => 'GET']], ['stateless' => true], null, []), new ResourceMetadata(null, null, null, [], ['stateless_attribute' => ['method' => 'GET', 'stateless' => true]], ['stateless' => true], null, []), $jsonapi];
    }

    private function getOperations(array $names): array
    {
        $operations = [];
        foreach ($names as $name) {
            $operations[$name] = ['method' => strtoupper($name), 'stateless' => null];
        }

        return $operations;
    }
}
