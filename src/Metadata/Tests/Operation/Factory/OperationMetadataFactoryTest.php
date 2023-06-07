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

namespace ApiPlatform\Metadata\Tests\Operation\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class OperationMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate(): void
    {
        $operation = new Get('/one', name: 'one');
        $resourceNameCollectionFactory = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->create()->willReturn(new ResourceNameCollection(['one']));
        $resourceMetadataCollectionFactory->create('one')->willReturn(new ResourceMetadataCollection('one', [
            new ApiResource(operations: [$operation]),
        ]));

        $operationMetadata = new OperationMetadataFactory($resourceNameCollectionFactory->reveal(), $resourceMetadataCollectionFactory->reveal());
        $this->assertEquals($operation, $operationMetadata->create('one'));
        $this->assertEquals($operation, $operationMetadata->create('/one'));
        $this->assertNull($operationMetadata->create('none'));
    }
}
