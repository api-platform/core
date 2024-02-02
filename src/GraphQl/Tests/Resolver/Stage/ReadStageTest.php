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

use ApiPlatform\GraphQl\Resolver\Stage\ReadStage;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ReadStageTest extends TestCase
{
    use ProphecyTrait;

    private ReadStage $readStage;
    private ObjectProphecy $iriConverterProphecy;
    private ObjectProphecy $providerProphecy;
    private ObjectProphecy $serializerContextBuilderProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->providerProphecy = $this->prophesize(ProviderInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $this->readStage = new ReadStage(
            $this->iriConverterProphecy->reveal(),
            $this->providerProphecy->reveal(),
            $this->serializerContextBuilderProphecy->reveal(),
            '_'
        );
    }

    /**
     * @dataProvider contextProvider
     */
    public function testApplyDisabled(array $context, object|array|null $expectedResult): void
    {
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withRead(false)->withName('item_query')->withClass($resourceClass);

        $result = ($this->readStage)($resourceClass, null, $operation, $context);

        $this->assertSame($expectedResult, $result);
    }

    public static function contextProvider(): array
    {
        return [
            'item context' => [['is_collection' => false], null],
            'collection context' => [['is_collection' => true], []],
        ];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testApplyItem(?string $identifier, ?object $item, bool $throwNotFound, ?object $expectedResult): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = [
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => ['id' => $identifier],
            'info' => $info,
        ];

        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName);
        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        if ($throwNotFound) {
            $this->iriConverterProphecy->getResourceFromIri($identifier, $normalizationContext)->willThrow(new ItemNotFoundException());
        } else {
            $this->iriConverterProphecy->getResourceFromIri($identifier, $normalizationContext)->willReturn($item);
        }

        $result = ($this->readStage)($resourceClass, null, $operation, $context);

        $this->assertSame($expectedResult, $result);
    }

    public static function itemProvider(): array
    {
        $item = new \stdClass();

        return [
            'no identifier' => [null, $item, false, null],
            'identifier' => ['identifier', $item, false, $item],
            'identifier not found' => ['identifier_not_found', $item, true, null],
        ];
    }

    /**
     * @dataProvider itemMutationOrSubscriptionProvider
     */
    public function testApplyMutationOrSubscription(bool $isMutation, bool $isSubscription, string $resourceClass, ?string $identifier, ?object $item, bool $throwNotFound, ?object $expectedResult, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $operationName = 'create';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $context = [
            'is_collection' => false,
            'is_mutation' => $isMutation,
            'is_subscription' => $isSubscription,
            'args' => ['input' => ['id' => $identifier]],
            'info' => $info,
        ];

        /** @var Operation $operation */
        $operation = (new Mutation())->withName($operationName)->withShortName('shortName');
        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        if ($throwNotFound) {
            $this->iriConverterProphecy->getResourceFromIri($identifier, $normalizationContext)->willThrow(new ItemNotFoundException());
        } else {
            $this->iriConverterProphecy->getResourceFromIri($identifier, $normalizationContext)->willReturn($item);
        }

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $result = ($this->readStage)($resourceClass, null, $operation, $context);

        $this->assertSame($expectedResult, $result);
    }

    public static function itemMutationOrSubscriptionProvider(): array
    {
        $item = new \stdClass();

        return [
            'no identifier' => [true, false, 'myResource', null, $item, false, null],
            'identifier' => [true, false, \stdClass::class, 'identifier', $item, false, $item],
            'identifier bad item' => [true, false, 'myResource', 'identifier', $item, false, $item, \UnexpectedValueException::class, 'Item "identifier" did not match expected type "shortName".'],
            'identifier not found' => [true, false, 'myResource', 'identifier_not_found', $item, true, null, NotFoundHttpException::class, 'Item "identifier_not_found" not found.'],
            'no identifier (subscription)' => [false, true, 'myResource', null, $item, false, null],
            'identifier (subscription)' => [false, true, \stdClass::class, 'identifier', $item, false, $item],
        ];
    }

    /**
     * @dataProvider collectionProvider
     */
    public function testApplyCollection(array $args, ?string $rootClass, ?array $source, array $expectedFilters, iterable $expectedResult): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $fieldName = 'resource';
        $info->fieldName = $fieldName;
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => $args,
            'info' => $info,
            'source' => $source,
        ];

        /** @var Operation $operation */
        $operation = (new QueryCollection())->withName($operationName);
        $normalizationContext = ['normalization' => true, 'operation' => $operation];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        $this->providerProphecy->provide($operation, [], $normalizationContext + ['filters' => $expectedFilters])->willReturn([]);
        $this->providerProphecy->provide($operation, ['id' => 3], $normalizationContext + ['filters' => $expectedFilters, 'linkClass' => 'myResource', 'linkProperty' => 'resource'])->willReturn(['resource']);

        $result = ($this->readStage)($resourceClass, $rootClass, $operation, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function testPreserveOrderOfOrderFiltersIfNested(): void
    {
        $operationName = 'collection_query';
        $resourceClass = 'myResource';
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $fieldName = 'resource';
        $info->fieldName = $fieldName;
        $context = [
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => false,
            'args' => [
                'order' => [
                    'some_field' => 'ASC',
                    'localField' => 'ASC',
                ],
            ],
            'info' => $info,
            'source' => null,
        ];

        /** @var Operation $operation */
        $operation = (new QueryCollection())->withName($operationName);

        $normalizationContext = ['normalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, true)->shouldBeCalled()->willReturn($normalizationContext);

        ($this->readStage)($resourceClass, $resourceClass, $operation, $context);

        $this->providerProphecy->provide($operation, [], Argument::that(fn ($args): bool => // Prophecy does not check the order of items in associative arrays. Checking if some.field comes first manually
array_search('some.field', array_keys($args['filters']['order']), true) <
        array_search('localField', array_keys($args['filters']['order']), true)))->shouldHaveBeenCalled();
    }

    public static function collectionProvider(): array
    {
        return [
            'no root class' => [[], null, null, [], []],
            'nominal' => [
                ['filter_list' => 'filtered', 'filter_field_list' => ['filtered1', 'filtered2']],
                'myResource',
                null,
                ['filter_list' => 'filtered', 'filter_field_list' => ['filtered1', 'filtered2'], 'filter.list' => 'filtered', 'filter_field' => ['filtered1', 'filtered2'], 'filter.field' => ['filtered1', 'filtered2']],
                [],
            ],
            'with array filter syntax' => [
                ['filter' => [['filterArg1' => 'filterValue1'], ['filterArg2' => 'filterValue2']]],
                'myResource',
                null,
                ['filter' => ['filterArg1' => 'filterValue1', 'filterArg2' => 'filterValue2']],
                [],
            ],
            'with resource' => [
                [],
                'myResource',
                ['resource' => [], ItemNormalizer::ITEM_IDENTIFIERS_KEY => ['id' => 3], ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => 'myResource'],
                [],
                ['resource'],
            ],
        ];
    }
}
