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
use ApiPlatform\GraphQl\Resolver\Stage\DeserializeStage;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DeserializeStageTest extends TestCase
{
    use ProphecyTrait;

    /** @var DeserializeStage */
    private $deserializeStage;
    private $resourceMetadataCollectionFactoryProphecy;
    private $denormalizerProphecy;
    private $serializerContextBuilderProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $this->deserializeStage = new DeserializeStage(
            $this->resourceMetadataCollectionFactoryProphecy->reveal(),
            $this->denormalizerProphecy->reveal(),
            $this->serializerContextBuilderProphecy->reveal()
        );
    }

    /**
     * @dataProvider objectToPopulateProvider
     *
     * @param object|null $objectToPopulate
     */
    public function testApplyDisabled($objectToPopulate): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withDeserialize(false)])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $result = ($this->deserializeStage)($objectToPopulate, $resourceClass, $operationName, []);

        $this->assertSame($objectToPopulate, $result);
    }

    /**
     * @dataProvider objectToPopulateProvider
     *
     * @param object|null $objectToPopulate
     */
    public function testApply($objectToPopulate, array $denormalizationContext): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $context = ['args' => ['input' => 'myInput']];
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => new Query()])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->serializerContextBuilderProphecy->create($resourceClass, $operationName, $context, false)->shouldBeCalled()->willReturn($denormalizationContext);

        $denormalizedData = new \stdClass();
        $this->denormalizerProphecy->denormalize($context['args']['input'], $resourceClass, ItemNormalizer::FORMAT, $denormalizationContext)->shouldBeCalled()->willReturn($denormalizedData);

        $result = ($this->deserializeStage)($objectToPopulate, $resourceClass, $operationName, $context);

        $this->assertSame($denormalizedData, $result);
    }

    public function objectToPopulateProvider(): array
    {
        return [
            'null' => [null, ['denormalization' => true]],
            'object' => [$object = new \stdClass(), ['denormalization' => true, ItemNormalizer::OBJECT_TO_POPULATE => $object]],
        ];
    }
}
