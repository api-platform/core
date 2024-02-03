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
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class NormalizeProcessorTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $resolveInfoProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
    }

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
    public function testProcessCollection($collection, $operation, $args, ?array $expectedResult, array $getFieldSelection, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $this->resolveInfoProphecy->getFieldSelection(1)->willReturn($getFieldSelection);
        $context = ['args' => $args, 'info' => $this->resolveInfoProphecy->reveal()];
        $serializerContext = ['resource_class' => $operation->getClass()];
        $normalizer = $this->prophesize(NormalizerInterface::class);

        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('create')->with($operation->getClass(), $operation, $context, normalization: true)->willReturn($serializerContext);
        foreach ($collection as $v) {
            $normalizer->normalize($v, 'graphql', $serializerContext)->willReturn(['normalized_item'])->shouldBeCalledOnce();
        }

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $processor = new NormalizeProcessor($normalizer->reveal(), $serializerContextBuilder, new Pagination());
        $result = $processor->process(\is_callable($collection) ? $collection($this) : $collection, $operation, [], $context);
        $this->assertSame($expectedResult, $result);
    }

    public static function processCollection(): iterable
    {
        $partialPaginatorFactory = function (self $that): PartialPaginatorInterface {
            $partialPaginatorProphecy = $that->prophesize(PartialPaginatorInterface::class);
            $partialPaginatorProphecy->count()->willReturn(2);
            $partialPaginatorProphecy->valid()->willReturn(false);
            $partialPaginatorProphecy->getItemsPerPage()->willReturn(2.0);
            $partialPaginatorProphecy->rewind();

            return $partialPaginatorProphecy->reveal();
        };

        yield 'cursor - not paginator' => [[], new QueryCollection(class: 'foo'), [], null, [], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface or ApiPlatform\State\Pagination\PartialPaginatorInterface.'];
        yield 'cursor - empty paginator' => [new ArrayPaginator([], 0, 0), new QueryCollection(class: 'foo'), [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 0, 2), new QueryCollection(class: 'foo'), [],  ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MA=='], ['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => false]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator with after cursor' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 1, 2), new QueryCollection(class: 'foo'), ['after' => 'MA=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator with bad after cursor' => [new ArrayPaginator([], 0, 0), new QueryCollection(class: 'foo'), ['after' => '-'], null, ['edges' => ['cursor' => []]], \UnexpectedValueException::class, 'Cursor - is invalid'];
        yield 'cursor - paginator with empty after cursor' => [new ArrayPaginator([], 0, 0), new QueryCollection(class: 'foo'), ['after' => ''], null, ['edges' => ['cursor' => []]], \UnexpectedValueException::class, 'Empty cursor is invalid'];
        yield 'cursor - paginator with before cursor' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 1, 1), new QueryCollection(class: 'foo'), ['before' => 'Mg=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => true]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator with bad before cursor' => [new ArrayPaginator([], 0, 0), new QueryCollection(class: 'foo'), ['before' => '-'], null, ['pageInfo' => ['endCursor' => true]], \UnexpectedValueException::class, 'Cursor - is invalid'];
        yield 'cursor - paginator with empty before cursor' => [new ArrayPaginator([], 0, 0), new QueryCollection(class: 'foo'), ['before' => ''], null, ['pageInfo' => ['endCursor' => true]], \UnexpectedValueException::class, 'Empty cursor is invalid'];
        yield 'cursor - paginator with last' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 1, 2), new QueryCollection(class: 'foo'), ['last' => 2], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - partial paginator' => [$partialPaginatorFactory, new QueryCollection(class: 'foo'), [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => false, 'hasPreviousPage' => false]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - partial paginator with after cursor' => [$partialPaginatorFactory, new QueryCollection(class: 'foo'), ['after' => 'MA=='], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]], ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];

        yield 'page - not paginator, itemsPerPage requested' => [[], (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], [], ['paginationInfo' => ['itemsPerPage' => true]], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PartialPaginatorInterface to return itemsPerPage field.'];
        yield 'page - not paginator, lastPage requested' => [[], (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], [], ['paginationInfo' => ['lastPage' => true]], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface to return lastPage field.'];
        yield 'page - not paginator, totalCount requested' => [[], (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], [], ['paginationInfo' => ['totalCount' => true]], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface to return totalCount field.'];
        yield 'page - empty paginator - itemsPerPage requested' => [new ArrayPaginator([], 0, 0), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [], 'paginationInfo' => ['itemsPerPage' => .0]], ['paginationInfo' => ['itemsPerPage' => true]]];
        yield 'page - empty paginator - lastPage requested' => [new ArrayPaginator([], 0, 0), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [], 'paginationInfo' => ['lastPage' => 1.0]], ['paginationInfo' => ['lastPage' => true]]];
        yield 'page - empty paginator - totalCount requested' => [new ArrayPaginator([], 0, 0), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [], 'paginationInfo' => ['totalCount' => .0]], ['paginationInfo' => ['totalCount' => true]]];
        yield 'page - paginator page 1 - itemsPerPage requested' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 0, 2), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [['normalized_item'], ['normalized_item']], 'paginationInfo' => ['itemsPerPage' => 2.0]], ['paginationInfo' => ['itemsPerPage' => true]]];
        yield 'page - paginator page 1 - lastPage requested' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 0, 2), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [['normalized_item'], ['normalized_item']], 'paginationInfo' => ['lastPage' => 2.0]], ['paginationInfo' => ['lastPage' => true]]];
        yield 'page - paginator page 1 - totalCount requested' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 0, 2), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [['normalized_item'], ['normalized_item']], 'paginationInfo' => ['totalCount' => 3.0]], ['paginationInfo' => ['totalCount' => true]]];
        yield 'page - paginator with page - itemsPerPage requested' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 2, 2), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [['normalized_item']], 'paginationInfo' => ['itemsPerPage' => 2.0]], ['paginationInfo' => ['itemsPerPage' => true]]];
        yield 'page - paginator with page - lastPage requested' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 2, 2), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [['normalized_item']], 'paginationInfo' => ['lastPage' => 2.0]], ['paginationInfo' => ['lastPage' => true]]];
        yield 'page - paginator with page - totalCount requested' => [new ArrayPaginator([(object) ['test' => 'a'], (object) ['test' => 'b'], (object) ['test' => 'c']], 2, 2), (new QueryCollection(class: 'foo'))->withPaginationType('page'), [], ['collection' => [['normalized_item']], 'paginationInfo' => ['totalCount' => 3.0]], ['paginationInfo' => ['totalCount' => true]]];
    }
}
