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

use ApiPlatform\GraphQl\Resolver\Stage\SerializeStage;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializeStageTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $normalizerProphecy;
    private ObjectProphecy $serializerContextBuilderProphecy;
    private ObjectProphecy $resolveInfoProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $this->resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
    }

    /**
     * @dataProvider applyDisabledProvider
     */
    public function testApplyDisabled(Operation $operation, bool $paginationEnabled, ?array $expectedResult): void
    {
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = $operation->withSerialize(false);

        $result = ($this->createSerializeStage($paginationEnabled))(null, $resourceClass, $operation, []);

        $this->assertSame($expectedResult, $result);
    }

    public static function applyDisabledProvider(): array
    {
        return [
            'item' => [new Query(), false, null],
            'collection with pagination' => [new QueryCollection(), true, ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]]],
            'collection without pagination' => [new QueryCollection(), false, []],
            'mutation' => [new Mutation(), false, ['clientMutationId' => null]],
            'subscription' => [new Subscription(), false, ['clientSubscriptionId' => null]],
        ];
    }

    /**
     * @dataProvider applyProvider
     */
    public function testApply(object|array $itemOrCollection, string $operationName, callable $contextFactory, bool $paginationEnabled, ?array $expectedResult): void
    {
        $context = $contextFactory($this);

        $resourceClass = 'myResource';
        $operation = $context['is_mutation'] ? new Mutation() : new Query();
        if ($context['is_subscription']) {
            $operation = new Subscription();
        }

        if ($context['is_collection'] ?? false) {
            $operation = new QueryCollection();
        }

        /** @var Operation $operation */
        $operation = $operation->withShortName('shortName')->withName($operationName)->withClass($resourceClass);

        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type(\stdClass::class), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(['normalized_item']);

        $result = ($this->createSerializeStage($paginationEnabled))($itemOrCollection, $resourceClass, $operation, $context);

        $this->assertSame($expectedResult, $result);
    }

    public static function applyProvider(): iterable
    {
        $defaultContextFactory = fn (self $that): array => [
            'args' => [],
            'info' => $that->resolveInfoProphecy->reveal(),
        ];

        yield 'item' => [new \stdClass(), 'item_query', fn (self $that): array => $defaultContextFactory($that) + ['is_collection' => false, 'is_mutation' => false, 'is_subscription' => false], false, ['normalized_item']];
        yield 'collection without pagination' => [[new \stdClass(), new \stdClass()], 'collection_query', fn (self $that): array => $defaultContextFactory($that) + ['is_collection' => true, 'is_mutation' => false, 'is_subscription' => false], false, [['normalized_item'], ['normalized_item']]];
        yield 'mutation' => [new \stdClass(), 'create', fn (self $that): array => array_merge($defaultContextFactory($that), ['args' => ['input' => ['clientMutationId' => 'clientMutationId']], 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false]), false, ['shortName' => ['normalized_item'], 'clientMutationId' => 'clientMutationId']];
        yield 'delete mutation' => [new \stdClass(), 'delete', fn (self $that): array => array_merge($defaultContextFactory($that), ['args' => ['input' => ['id' => '/iri/4']], 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false]), false, ['shortName' => ['id' => '/iri/4'], 'clientMutationId' => null]];
        yield 'subscription' => [new \stdClass(), 'update', fn (self $that): array => array_merge($defaultContextFactory($that), ['args' => ['input' => ['clientSubscriptionId' => 'clientSubscriptionId']], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]), false, ['shortName' => ['normalized_item'], 'clientSubscriptionId' => 'clientSubscriptionId']];
    }

    /**
     * @dataProvider applyCollectionWithPaginationProvider
     */
    public function testApplyCollectionWithPagination(iterable|callable $collection, array $args, ?array $expectedResult, bool $pageBasedPagination, array $getFieldSelection = [], ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $this->resolveInfoProphecy->getFieldSelection(1)->willReturn($getFieldSelection);
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => $args,
            'info' => $this->resolveInfoProphecy->reveal(),
        ];

        /** @var Operation $operation */
        $operation = (new QueryCollection())->withShortName('shortName')->withName($operationName);
        if ($pageBasedPagination) {
            $operation = $operation->withPaginationType('page');
        }

        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type(\stdClass::class), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(['normalized_item']);

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $result = ($this->createSerializeStage(true))(\is_callable($collection) ? $collection($this) : $collection, $resourceClass, $operation, $context);

        $this->assertSame($expectedResult, $result);
    }

    public static function applyCollectionWithPaginationProvider(): iterable
    {
        $partialPaginatorFactory = function (self $that): PartialPaginatorInterface {
            $partialPaginatorProphecy = $that->prophesize(PartialPaginatorInterface::class);
            $partialPaginatorProphecy->count()->willReturn(2);
            $partialPaginatorProphecy->valid()->willReturn(false);
            $partialPaginatorProphecy->getItemsPerPage()->willReturn(2.0);
            $partialPaginatorProphecy->rewind();

            return $partialPaginatorProphecy->reveal();
        };

        yield 'cursor - not paginator' => [[], [], null, false, [], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface or ApiPlatform\State\Pagination\PartialPaginatorInterface.'];
        yield 'cursor - empty paginator' => [new ArrayPaginator([], 0, 0), [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]], false, ['totalCount' => true, 'edges' => [], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 0, 2), [], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MA=='], ['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => false]], false, ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator with after cursor' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 2), ['after' => 'MA=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]], false, ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator with bad after cursor' => [new ArrayPaginator([], 0, 0), ['after' => '-'], null, false, ['pageInfo' => ['endCursor' => true]], \UnexpectedValueException::class, 'Cursor - is invalid'];
        yield 'cursor - paginator with empty after cursor' => [new ArrayPaginator([], 0, 0), ['after' => ''], null, false, ['pageInfo' => ['endCursor' => true]], \UnexpectedValueException::class, 'Empty cursor is invalid'];
        yield 'cursor - paginator with before cursor' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 1), ['before' => 'Mg=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => true]], false, ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - paginator with bad before cursor' => [new ArrayPaginator([], 0, 0), ['before' => '-'], null, false, ['pageInfo' => ['endCursor' => true]], \UnexpectedValueException::class, 'Cursor - is invalid'];
        yield 'cursor - paginator with empty before cursor' => [new ArrayPaginator([], 0, 0), ['before' => ''], null, false, ['pageInfo' => ['endCursor' => true]], \UnexpectedValueException::class, 'Empty cursor is invalid'];
        yield 'cursor - paginator with last' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 2), ['last' => 2], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]], false, ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - partial paginator' => [$partialPaginatorFactory, [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => false, 'hasPreviousPage' => false]], false, ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'cursor - partial paginator with after cursor' => [$partialPaginatorFactory, ['after' => 'MA=='], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]], false, ['totalCount' => true, 'edges' => ['cursor' => true], 'pageInfo' => ['startCursor' => true, 'endCursor' => true, 'hasNextPage' => true, 'hasPreviousPage' => true]]];

        yield 'page - not paginator, itemsPerPage requested' => [[], [], null, true, ['paginationInfo' => ['itemsPerPage' => true]], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PartialPaginatorInterface to return itemsPerPage field.'];
        yield 'page - not paginator, lastPage requested' => [[], [], null, true, ['paginationInfo' => ['lastPage' => true]], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface to return lastPage field.'];
        yield 'page - not paginator, totalCount requested' => [[], [], null, true, ['paginationInfo' => ['totalCount' => true]], \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface to return totalCount field.'];
        yield 'page - empty paginator - itemsPerPage requested' => [new ArrayPaginator([], 0, 0), [], ['collection' => [], 'paginationInfo' => ['itemsPerPage' => .0]], true, ['paginationInfo' => ['itemsPerPage' => true]]];
        yield 'page - empty paginator - lastPage requested' => [new ArrayPaginator([], 0, 0), [], ['collection' => [], 'paginationInfo' => ['lastPage' => 1.0]], true, ['paginationInfo' => ['lastPage' => true]]];
        yield 'page - empty paginator - totalCount requested' => [new ArrayPaginator([], 0, 0), [], ['collection' => [], 'paginationInfo' => ['totalCount' => .0]], true, ['paginationInfo' => ['totalCount' => true]]];
        yield 'page - paginator page 1 - itemsPerPage requested' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 0, 2), [], ['collection' => [['normalized_item'], ['normalized_item']], 'paginationInfo' => ['itemsPerPage' => 2.0]], true, ['paginationInfo' => ['itemsPerPage' => true]]];
        yield 'page - paginator page 1 - lastPage requested' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 0, 2), [], ['collection' => [['normalized_item'], ['normalized_item']], 'paginationInfo' => ['lastPage' => 2.0]], true, ['paginationInfo' => ['lastPage' => true]]];
        yield 'page - paginator page 1 - totalCount requested' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 0, 2), [], ['collection' => [['normalized_item'], ['normalized_item']], 'paginationInfo' => ['totalCount' => 3.0]], true, ['paginationInfo' => ['totalCount' => true]]];
        yield 'page - paginator with page - itemsPerPage requested' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 2, 2), [], ['collection' => [['normalized_item']], 'paginationInfo' => ['itemsPerPage' => 2.0]], true, ['paginationInfo' => ['itemsPerPage' => true]]];
        yield 'page - paginator with page - lastPage requested' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 2, 2), [], ['collection' => [['normalized_item']], 'paginationInfo' => ['lastPage' => 2.0]], true, ['paginationInfo' => ['lastPage' => true]]];
        yield 'page - paginator with page - totalCount requested' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 2, 2), [], ['collection' => [['normalized_item']], 'paginationInfo' => ['totalCount' => 3.0]], true, ['paginationInfo' => ['totalCount' => true]]];
    }

    /**
     * @dataProvider applyCollectionWithPaginationShouldNotCountItemsUnlessFieldRequestedProvider
     */
    public function testApplyCollectionWithPaginationShouldNotCountItemsUnlessFieldRequested(bool $pageBasedPagination, array $getFieldSelection = [], bool $getTotalItemsCalled = false): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $this->resolveInfoProphecy->getFieldSelection(1)->willReturn($getFieldSelection);
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => [],
            'info' => $this->resolveInfoProphecy->reveal(),
        ];
        $collectionProphecy = $this->prophesize(PaginatorInterface::class);
        $collectionProphecy->getTotalItems()->willReturn(1);
        $collectionProphecy->count()->willReturn(1);
        $collectionProphecy->getItemsPerPage()->willReturn(20.0);
        $collectionProphecy->valid()->willReturn(false);
        $collectionProphecy->rewind();
        if ($getTotalItemsCalled) {
            $collectionProphecy->getTotalItems()->shouldBeCalledOnce();
        } else {
            $collectionProphecy->getTotalItems()->shouldNotBeCalled();
        }

        /** @var Operation $operation */
        $operation = (new QueryCollection())->withShortName('shortName')->withName($operationName);
        if ($pageBasedPagination) {
            $operation = $operation->withPaginationType('page');
        }

        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type(\stdClass::class), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(['normalized_item']);

        ($this->createSerializeStage(true))($collectionProphecy->reveal(), $resourceClass, $operation, $context);
    }

    public static function applyCollectionWithPaginationShouldNotCountItemsUnlessFieldRequestedProvider(): iterable
    {
        yield 'cursor - totalCount requested' => [false, ['totalCount' => true], true];
        yield 'cursor - totalCount not requested' => [false, [], false];
        yield 'page - totalCount requested' => [true, ['paginationInfo' => ['totalCount' => true]], true];
        yield 'page - totalCount not requested' => [true, [], false];
    }

    public function testApplyBadNormalizedData(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $context = ['is_collection' => false, 'is_mutation' => false, 'is_subscription' => false, 'args' => [], 'info' => $this->prophesize(ResolveInfo::class)->reveal()];
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName);

        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        $this->normalizerProphecy->normalize(Argument::type(\stdClass::class), ItemNormalizer::FORMAT, $normalizationContext)->willReturn(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected serialized data to be a nullable array.');

        ($this->createSerializeStage(false))(new \stdClass(), $resourceClass, $operation, $context);
    }

    private function createSerializeStage(bool $paginationEnabled): SerializeStage
    {
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Argument::type('string'))->willReturn(new ResourceMetadataCollection(''));
        $pagination = new Pagination([], ['enabled' => $paginationEnabled]);

        return new SerializeStage(
            $this->normalizerProphecy->reveal(),
            $this->serializerContextBuilderProphecy->reveal(),
            $pagination
        );
    }
}
