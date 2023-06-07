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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\OperationNameResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class OperationNameResourceMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider operationProvider
     */
    public function testGeneratesName(Operation $operation, string $expectedOperationName): void
    {
        $decorated = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->create('a')->willReturn(new ResourceMetadataCollection('a', [
            new ApiResource(operations: [$operation]),
        ]));

        $operationNameResourceMetadataFactory = new OperationNameResourceMetadataCollectionFactory($decorated->reveal());
        $result = $operationNameResourceMetadataFactory->create('a');

        $this->assertEquals($operation->withName($expectedOperationName), $result->getOperation($expectedOperationName));
    }

    public function operationProvider(): array
    {
        return [
            [new Get(), '_api_a_get'],
            [new Get(shortName: 'Foo'), '_api_Foo_get'],
            [new Get(name: 'test'), 'test'],
            [new Get(routePrefix: 'foo'), '_api_foo_get'],
            [new Get(uriTemplate: '/foo'), '_api_/foo_get'],
            [new Get(routePrefix: '/admin', uriTemplate: '/foo'), '_api_/admin/foo_get'],
        ];
    }
}
