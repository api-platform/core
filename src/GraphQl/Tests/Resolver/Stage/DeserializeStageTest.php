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

namespace ApiPlatform\GraphQl\Tests\Resolver\Stage;

use ApiPlatform\GraphQl\Resolver\Stage\DeserializeStage;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DeserializeStageTest extends TestCase
{
    use ProphecyTrait;

    private DeserializeStage $deserializeStage;
    private ObjectProphecy $denormalizerProphecy;
    private ObjectProphecy $serializerContextBuilderProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $this->deserializeStage = new DeserializeStage(
            $this->denormalizerProphecy->reveal(),
            $this->serializerContextBuilderProphecy->reveal()
        );
    }

    /**
     * @dataProvider objectToPopulateProvider
     */
    public function testApplyDisabled(?object $objectToPopulate): void
    {
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withName('item_query')->withClass($resourceClass)->withDeserialize(false);
        $result = ($this->deserializeStage)($objectToPopulate, $resourceClass, $operation, []);

        $this->assertSame($objectToPopulate, $result);
    }

    /**
     * @dataProvider objectToPopulateProvider
     */
    public function testApply(?object $objectToPopulate, array $denormalizationContext): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName)->withClass($resourceClass);
        $context = ['args' => ['input' => 'myInput']];

        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, false)->shouldBeCalled()->willReturn($denormalizationContext);

        $denormalizedData = new \stdClass();
        $this->denormalizerProphecy->denormalize($context['args']['input'], $resourceClass, ItemNormalizer::FORMAT, $denormalizationContext)->shouldBeCalled()->willReturn($denormalizedData);

        $result = ($this->deserializeStage)($objectToPopulate, $resourceClass, $operation, $context);

        $this->assertSame($denormalizedData, $result);
    }

    public static function objectToPopulateProvider(): array
    {
        return [
            'null' => [null, ['denormalization' => true]],
            'object' => [$object = new \stdClass(), ['denormalization' => true, ItemNormalizer::OBJECT_TO_POPULATE => $object]],
        ];
    }
}
