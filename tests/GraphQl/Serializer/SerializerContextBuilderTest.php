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

namespace ApiPlatform\Core\Tests\GraphQl\Serializer;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializerContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    /** @var SerializerContextBuilder */
    private $serializerContextBuilder;
    private $resourceMetadataCollectionFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $this->serializerContextBuilder = $this->buildSerializerContextBuilder();
    }

    private function buildSerializerContextBuilder(?AdvancedNameConverterInterface $advancedNameConverter = null): SerializerContextBuilder
    {
        return new SerializerContextBuilder($this->resourceMetadataCollectionFactoryProphecy->reveal(), $advancedNameConverter ?? new CustomConverter());
    }

    /**
     * @dataProvider createNormalizationContextProvider
     */
    public function testCreateNormalizationContext(?string $resourceClass, string $operationName, array $fields, bool $isMutation, bool $isSubscription, bool $noInfo, array $expectedContext, ?AdvancedNameConverterInterface $advancedNameConverter = null, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $resolverContext = [
            'is_mutation' => $isMutation,
            'is_subscription' => $isSubscription,
        ];

        if ($noInfo) {
            $resolverContext['fields'] = $fields;
        } else {
            $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
            $resolveInfoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);
            $resolverContext['info'] = $resolveInfoProphecy->reveal();
        }

        $operation = !$isMutation && !$isSubscription ? new Query() : new Mutation();
        if ($isSubscription) {
            $operation = new Subscription();
        }

        if ($resourceClass) {
            $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => $operation->withShortName('shortName')->withInput(['class' => 'inputClass'])->withOutput(['class' => 'outputClass'])->withNormalizationContext(['groups' => ['normalization_group']])])]));
        }

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $serializerContextBuilder = $this->serializerContextBuilder;
        if ($advancedNameConverter) {
            $serializerContextBuilder = $this->buildSerializerContextBuilder($advancedNameConverter);
        }

        $context = $serializerContextBuilder->create($resourceClass, $operationName, $resolverContext, true);

        $this->assertSame($expectedContext, $context);
    }

    public function createNormalizationContextProvider(): array
    {
        $advancedNameConverter = $this->prophesize(AdvancedNameConverterInterface::class);
        $advancedNameConverter->denormalize('field', 'myResource', null, Argument::type('array'))->willReturn('denormalizedField');

        return [
            'nominal' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                ['_id' => 3, 'field' => 'foo'],
                false,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                    'attributes' => [
                        'id' => 3,
                        'field' => 'foo',
                    ],
                ],
            ],
            'nominal with advanced name converter' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                ['_id' => 3, 'field' => 'foo'],
                false,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                    'attributes' => [
                        'id' => 3,
                        'denormalizedField' => 'foo',
                    ],
                ],
                $advancedNameConverter->reveal(),
            ],
            'nominal collection' => [
                $resourceClass = 'myResource',
                $operationName = 'collection_query',
                ['edges' => ['node' => ['nodeField' => 'baz']]],
                false,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                    'attributes' => [
                        'nodeField' => 'baz',
                    ],
                ],
            ],
            'no resource class' => [
                $resourceClass = null,
                $operationName = 'item_query',
                ['related' => ['_id' => 9]],
                false,
                false,
                false,
                [
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'related' => ['id' => 9],
                    ],
                ],
            ],
            'mutation' => [
                $resourceClass = 'myResource',
                $operationName = 'create',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                true,
                false,
                false,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                    'attributes' => [
                        'id' => 7,
                        'related' => ['field' => 'bar'],
                    ],
                ],
            ],
            'mutation without resource class' => [
                $resourceClass = null,
                $operationName = 'create',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                true,
                false,
                false,
                [],
                null,
                \LogicException::class,
                'An operation should always exist for a mutation or a subscription.',
            ],
            'subscription (using fields in context)' => [
                $resourceClass = 'myResource',
                $operationName = 'update',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                false,
                true,
                true,
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'no_resolver_data' => true,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                    'attributes' => [
                        'id' => 7,
                        'related' => ['field' => 'bar'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createDenormalizationContextProvider
     */
    public function testCreateDenormalizationContext(?string $resourceClass, string $operationName, array $expectedContext): void
    {
        if ($resourceClass) {
            $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Mutation())->withShortName('shortName')->withInput(['class' => 'inputClass'])->withOutput(['class' => 'outputClass'])->withDenormalizationContext(['groups' => ['denormalization_group']])])]));
        }

        $context = $this->serializerContextBuilder->create($resourceClass, $operationName, [], false);

        $this->assertSame($expectedContext, $context);
    }

    public function createDenormalizationContextProvider(): array
    {
        return [
            'nominal' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                [
                    'groups' => ['denormalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
            ],
            'no resource class' => [
                $resourceClass = null,
                $operationName = 'item_query',
                [
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                ],
            ],
        ];
    }
}
