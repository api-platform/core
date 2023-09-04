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

namespace ApiPlatform\GraphQl\Tests\State\Provider;

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\GraphQl\State\Provider\DenormalizeProvider;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DenormalizeProviderTest extends TestCase
{
    public function testProvide(): void
    {
        $objectToPopulate = null;
        $context = ['args' => ['input' => ['test']]];
        $operation = new Mutation(class: 'dummy');
        $serializerContext = ['resource_class' => $operation->getClass()];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($objectToPopulate);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('create')->with($operation->getClass(), $operation, $context, normalization: false)->willReturn($serializerContext);
        $denormalizer->expects($this->once())->method('denormalize')->with(['test'], 'dummy', 'graphql', $serializerContext)->willReturn(new \stdClass());
        $provider = new DenormalizeProvider($decorated, $denormalizer, $serializerContextBuilder);
        $provider->provide($operation, [], $context);
    }

    public function testProvideWithObjectToPopulate(): void
    {
        $objectToPopulate = new \stdClass();
        $context = ['args' => ['input' => ['test']]];
        $operation = new Mutation(class: 'dummy');
        $serializerContext = ['resource_class' => $operation->getClass(), 'object_to_populate' => $objectToPopulate];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($objectToPopulate);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('create')->with($operation->getClass(), $operation, $context, normalization: false)->willReturn($serializerContext);
        $denormalizer->expects($this->once())->method('denormalize')->with(['test'], 'dummy', 'graphql', $serializerContext)->willReturn(new \stdClass());
        $provider = new DenormalizeProvider($decorated, $denormalizer, $serializerContextBuilder);
        $provider->provide($operation, [], $context);
    }

    public function testProvideNotCalledWithQuery(): void
    {
        $objectToPopulate = new \stdClass();
        $context = ['args' => ['input' => ['test']]];
        $operation = new Query(class: 'dummy');
        $serializerContext = ['resource_class' => $operation->getClass()];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($objectToPopulate);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->never())->method('create')->with($operation->getClass(), $operation, $context, normalization: false)->willReturn($serializerContext);
        $denormalizer->expects($this->never())->method('denormalize')->with(['test'], 'dummy', 'graphql', $serializerContext)->willReturn(new \stdClass());
        $provider = new DenormalizeProvider($decorated, $denormalizer, $serializerContextBuilder);
        $provider->provide($operation, [], $context);
    }

    public function testProvideNotCalledWithoutDeserialize(): void
    {
        $objectToPopulate = new \stdClass();
        $context = ['args' => ['input' => ['test']]];
        $operation = new Query(class: 'dummy', deserialize: false);
        $serializerContext = ['resource_class' => $operation->getClass()];
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($objectToPopulate);
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->never())->method('create')->with($operation->getClass(), $operation, $context, normalization: false)->willReturn($serializerContext);
        $denormalizer->expects($this->never())->method('denormalize')->with(['test'], 'dummy', 'graphql', $serializerContext)->willReturn(new \stdClass());
        $provider = new DenormalizeProvider($decorated, $denormalizer, $serializerContextBuilder);
        $provider->provide($operation, [], $context);
    }
}
