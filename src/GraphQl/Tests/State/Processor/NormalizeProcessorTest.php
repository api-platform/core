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

namespace ApiPlatform\GraphQl\Tests\State\Processor;

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\GraphQl\State\Processor\NormalizeProcessor;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class NormalizeProcessorTest extends TestCase
{
    /**
     * @dataProvider processItems
     */
    public function testProcess($body, $operation): void
    {
        $context = ['args' => []];
        $serializerContext = ['resource_class' => $operation->getClass()];
        $normalizer = $this->createMock(NormalizerInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('create')->with($operation->getClass(), $operation, $context, normalization: true)->willReturn($serializerContext);
        $normalizer->expects($this->once())->method('normalize')->with($body, 'graphql', $serializerContext);
        $processor = new NormalizeProcessor($normalizer, $serializerContextBuilder, new Pagination());
        $processor->process($body, $operation, [], $context);
    }

    public static function processItems(): array
    {
        return [
            [new \stdClass(), new Query(class: 'foo')],
            [new \stdClass(), new Mutation(class: 'foo', shortName: 'Foo')],
            [new \stdClass(), new Subscription(class: 'foo', shortName: 'Foo')],
        ];
    }

    /**
     * @dataProvider processCollection
     */
    public function testProcessCollection($body, $operation): void
    {
        $context = ['args' => []];
        $serializerContext = ['resource_class' => $operation->getClass()];
        $normalizer = $this->createMock(NormalizerInterface::class);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('create')->with($operation->getClass(), $operation, $context, normalization: true)->willReturn($serializerContext);
        foreach ($body as $v) {
            $normalizer->expects($this->once())->method('normalize')->with($v, 'graphql', $serializerContext);
        }

        $processor = new NormalizeProcessor($normalizer, $serializerContextBuilder, new Pagination());
        $processor->process($body, $operation, [], $context);
    }

    public static function processCollection(): array
    {
        return [
            [new ArrayPaginator([new \stdClass()], 0, 1), new QueryCollection(class: 'foo')],
        ];
    }
}
