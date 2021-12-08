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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Stage;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\GraphQl\Resolver\Stage\WriteStage;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class WriteStageTest extends TestCase
{
    use ProphecyTrait;

    /** @var WriteStage */
    private $writeStage;
    private $resourceMetadataCollectionFactoryProphecy;
    private $processorProphecy;
    private $serializerContextBuilderProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->processorProphecy = $this->prophesize(ProcessorInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $this->writeStage = new WriteStage(
            $this->resourceMetadataCollectionFactoryProphecy->reveal(),
            $this->processorProphecy->reveal(),
            $this->serializerContextBuilderProphecy->reveal()
        );
    }

    public function testNoData(): void
    {
        $resourceClass = 'myResource';
        $operationName = 'item_query';
        $resourceMetadata = (new ApiResource())->withGraphQlOperations([
            $operationName => (new Query()),
        ]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [$resourceMetadata]));

        $result = ($this->writeStage)(null, $resourceClass, $operationName, []);

        $this->assertNull($result);
    }

    public function testApplyDisabled(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([
            $operationName => (new Query())->withWrite(false),
        ])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $data = new \stdClass();
        $result = ($this->writeStage)($data, $resourceClass, $operationName, []);

        $this->assertSame($data, $result);
    }

    public function testApply(): void
    {
        $operationName = 'create';
        $resourceClass = 'myResource';
        $context = [];
        $operation = (new Mutation())->withName($operationName);
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([
            $operationName => $operation,
        ])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $denormalizationContext = ['denormalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operationName, $context, false)->willReturn($denormalizationContext);

        $data = new \stdClass();
        $processedData = new \stdClass();
        $this->processorProphecy->process($data, [], $operationName, ['operation' => $operation] + $denormalizationContext)->shouldBeCalled()->willReturn($processedData);

        $result = ($this->writeStage)($data, $resourceClass, $operationName, $context);

        $this->assertSame($processedData, $result);
    }
}
