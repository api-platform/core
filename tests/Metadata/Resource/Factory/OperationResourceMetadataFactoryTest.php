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

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Api\OperationType;
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
    public function testCreateOperation(ResourceMetadata $before, ResourceMetadata $after, array $formats = [], $disableRest = false ): void
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($before);

        $this->assertEquals($after, (new OperationResourceMetadataFactory($decoratedProphecy->reveal(), $formats, $disableRest))->create(Dummy::class));
    }

    public function getMetadata(): iterable
    {
        $jsonapi = ['jsonapi' => ['application/vnd.api+json']];

        // Item operations
        yield [new ResourceMetadata(null, null, null, null, [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get', 'put', 'delete']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, null, [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get', 'put', 'patch', 'delete']), [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['get'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['get']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], [], null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, null, null, null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), [], null, [], []), [], true];
        yield [new ResourceMetadata(null, null, null, ['put'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['put']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['delete'], [], null, [], []), new ResourceMetadata(null, null, null, $this->getOperations(['delete']), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], [], null, [], []), new ResourceMetadata(null, null, null, array_merge(['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], $this->getPlaceholderOperation()), [], null, [], [])];
        yield [new ResourceMetadata(null, null, null, ['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], [], null, [], []), new ResourceMetadata(null, null, null, array_merge(['patch' => ['method' => 'PATCH', 'route_name' => 'patch']], $this->getPlaceholderOperation()), [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['untouched' => ['method' => 'GET']], [], null, [], []), new ResourceMetadata(null, null, null, ['untouched' => ['method' => 'GET']], [], null, [], []), $jsonapi];
        yield [new ResourceMetadata(null, null, null, ['untouched_custom' => ['route_name' => 'custom_route']], [], null, [], []), new ResourceMetadata(null, null, null, array_merge(['untouched_custom' => ['route_name' => 'custom_route']], $this->getPlaceholderOperation()), [], null, [], []), $jsonapi];

        // Collection operations
        yield [new ResourceMetadata(null, null, null, [], null, null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), $this->getOperations(['get', 'post'], OperationType::COLLECTION), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['get'], null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), $this->getOperations(['get'], OperationType::COLLECTION), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['post'], null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), $this->getOperations(['post'], OperationType::COLLECTION), null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['options' => ['method' => 'OPTIONS', 'route_name' => 'options']], null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), ['options' => ['route_name' => 'options', 'method' => 'OPTIONS']], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['untouched' => ['method' => 'GET']], null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), ['untouched' => ['method' => 'GET']], null, [], [])];
        yield [new ResourceMetadata(null, null, null, [], ['untouched_custom' => ['route_name' => 'custom_route']], null, [], []), new ResourceMetadata(null, null, null, $this->getPlaceholderOperation(), ['untouched_custom' => ['route_name' => 'custom_route']], null, [], [])];
    }

    private function getOperations(array $names, $operationType = OperationType::ITEM): array
    {
        $operations = [];
        foreach ($names as $name) {
            $operations[$name] = ['method' => strtoupper($name)];
        }

        if (OperationType::ITEM === $operationType && !isset($operations['get'])) {
            return array_merge($operations, $this->getPlaceholderOperation());
        }

        return $operations;
    }

    private function getPlaceholderOperation(): array
    {
        return ['get' => ['method' => 'GET', 'read' => false, 'output' => ['class' => false], 'controller' => NotFoundAction::class]];
    }
}
