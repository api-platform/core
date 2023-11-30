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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
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
            'info' => $that->prophesize(ResolveInfo::class)->reveal(),
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
    public function testApplyCollectionWithPagination(iterable|callable $collection, array $args, ?array $expectedResult, string $expectedExceptionClass = null, string $expectedExceptionMessage = null): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => $args,
            'info' => self::createMock(ResolveInfo::class),
        ];

        /** @var Operation $operation */
        $operation = (new QueryCollection())->withShortName('shortName')->withName($operationName);

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
            $partialPaginatorProphecy->rewind();

            return $partialPaginatorProphecy->reveal();
        };

        yield 'not paginator' => [[], [], null, \LogicException::class, 'Collection returned by the collection data provider must implement ApiPlatform\State\Pagination\PaginatorInterface'];
        yield 'empty paginator' => [new ArrayPaginator([], 0, 0), [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]]];
        yield 'paginator' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 0, 2), [], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MA=='], ['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => false]]];
        yield 'paginator with after cursor' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 2), ['after' => 'MA=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]]];
        yield 'paginator with bad after cursor' => [new ArrayPaginator([], 0, 0), ['after' => '-'], null, \UnexpectedValueException::class, 'Cursor - is invalid'];
        yield 'paginator with empty after cursor' => [new ArrayPaginator([], 0, 0), ['after' => ''], null, \UnexpectedValueException::class, 'Empty cursor is invalid'];
        yield 'paginator with before cursor' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 1), ['before' => 'Mg=='], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'MQ==', 'hasNextPage' => true, 'hasPreviousPage' => true]]];
        yield 'paginator with bad before cursor' => [new ArrayPaginator([], 0, 0), ['before' => '-'], null, \UnexpectedValueException::class, 'Cursor - is invalid'];
        yield 'paginator with empty before cursor' => [new ArrayPaginator([], 0, 0), ['before' => ''], null, \UnexpectedValueException::class, 'Empty cursor is invalid'];
        yield 'paginator with last' => [new ArrayPaginator([new \stdClass(), new \stdClass(), new \stdClass()], 1, 2), ['last' => 2], ['totalCount' => 3., 'edges' => [['node' => ['normalized_item'], 'cursor' => 'MQ=='], ['node' => ['normalized_item'], 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]]];
        yield 'partial paginator' => [$partialPaginatorFactory, [], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => false, 'hasPreviousPage' => false]]];
        yield 'partial paginator with after cursor' => [$partialPaginatorFactory, ['after' => 'MA=='], ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => 'MQ==', 'endCursor' => 'Mg==', 'hasNextPage' => false, 'hasPreviousPage' => true]]];
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
