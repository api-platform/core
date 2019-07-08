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

    public function getMetadata(): iterable
    {
        $jsonapi = ['jsonapi' => ['application/vnd.api+json']];

        // Item operations
        yield [new ResourceMetadata(null, null, null, null, [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get', 'put', 'delete']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, null, [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get', 'put', 'patch', 'delete'], $jsonapi), [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['get'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['put'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['put']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['delete'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['delete']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], [], null, [], []), new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch', 'input' => ['formats' => []], 'output' => ['formats' => []]]], [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], [], null, [], []), new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch', 'input' => ['formats' => $jsonapi], 'output' => ['formats' => $jsonapi]]], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['untouched' => ['method' => 'GET']], [], null, [], []), new ResourceMetadata(null, null, null, ['untouched' => ['method' => 'GET', 'input' => ['formats' => $jsonapi], 'output' => ['formats' => $jsonapi]]], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['untouched_custom' => ['route_name' => 'custom_route']], [], null, [], []), new ResourceMetadata(null, null, null, ['untouched_custom' => ['route_name' => 'custom_route', 'input' => ['formats' => $jsonapi], 'output' => ['formats' => $jsonapi]]], [], null, [], []), $jsonapi];

        // Collection operations
        yield [new ResourceMetadata(null, null, null, [], null, null, [], []), new ResourceMetadata(null, null, null, [], $this->getOperations(['get', 'post']), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['get'], null, [], []), new ResourceMetadata(null, null, null, [], $this->getOperations(['get']), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['post'], null, [], []), new ResourceMetadata(null, null, null, [], $this->getOperations(['post']), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['options' => ['method' => 'OPTIONS', 'route_name' => 'options']], null, [], []), new ResourceMetadata(null, null, null, [], ['options' => ['route_name' => 'options', 'method' => 'OPTIONS', 'input' => ['formats' => []], 'output' => ['formats' => []]]], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['untouched' => ['method' => 'GET']], null, [], []), new ResourceMetadata(null, null, null, [], ['untouched' => ['method' => 'GET', 'input' => ['formats' => []], 'output' => ['formats' => []]]], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['untouched_custom' => ['route_name' => 'custom_route']], null, [], []), new ResourceMetadata(null, null, null, [], ['untouched_custom' => ['route_name' => 'custom_route', 'input' => ['formats' => []], 'output' => ['formats' => []]]], null, [], [])];
    }

    private function getOperations(array $names, array $formats = []): array
    {
        $baseOperation = [
            'input' => ['formats' => $formats],
            'output' => ['formats' => $formats],
        ];

        $operations = [];
        foreach ($names as $name) {
            $operations[$name] = ['method' => strtoupper($name)] + $baseOperation;
        }

        return $operations;
    }
}
