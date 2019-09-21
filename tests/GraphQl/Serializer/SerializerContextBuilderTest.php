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

use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilder;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializerContextBuilderTest extends TestCase
{
    /** @var SerializerContextBuilder */
    private $serializerContextBuilder;
    private $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $this->serializerContextBuilder = new SerializerContextBuilder(
            $this->resourceMetadataFactoryProphecy->reveal(),
            new CustomConverter()
        );
    }

    /**
     * @dataProvider createNormalizationContextProvider
     */
    public function testCreateNormalizationContext(?string $resourceClass, string $operationName, array $fields, array $expectedContext, bool $isMutation, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->getFieldSelection(PHP_INT_MAX)->willReturn($fields);
        $resolverContext = [
            'info' => $resolveInfoProphecy->reveal(),
            'is_mutation' => $isMutation,
        ];

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(
            (new ResourceMetadata('shortName'))
                ->withGraphql([
                    $operationName => [
                        'input' => ['class' => 'inputClass'],
                        'output' => ['class' => 'outputClass'],
                        'normalization_context' => ['groups' => ['normalization_group']],
                    ],
                ])
        );

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $context = $this->serializerContextBuilder->create($resourceClass, $operationName, $resolverContext, true);

        $this->assertSame($expectedContext, $context);
    }

    public function createNormalizationContextProvider(): array
    {
        return [
            'nominal' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                ['_id' => 3, 'field' => 'foo'],
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'id' => 3,
                        'field' => 'foo',
                    ],
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
                false,
            ],
            'nominal collection' => [
                $resourceClass = 'myResource',
                $operationName = 'collection_query',
                ['edges' => ['node' => ['nodeField' => 'baz']]],
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'nodeField' => 'baz',
                    ],
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
                false,
            ],
            'no resource class' => [
                $resourceClass = null,
                $operationName = 'item_query',
                ['related' => ['_id' => 9]],
                [
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'related' => ['id' => 9],
                    ],
                ],
                false,
            ],
            'mutation' => [
                $resourceClass = 'myResource',
                $operationName = 'create',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                [
                    'groups' => ['normalization_group'],
                    'resource_class' => $resourceClass,
                    'graphql_operation_name' => $operationName,
                    'attributes' => [
                        'id' => 7,
                        'related' => ['field' => 'bar'],
                    ],
                    'input' => ['class' => 'inputClass'],
                    'output' => ['class' => 'outputClass'],
                ],
                true,
            ],
            'mutation without resource class' => [
                $resourceClass = null,
                $operationName = 'create',
                ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
                [],
                true,
                \LogicException::class,
                'ResourceMetadata should always exist for a mutation.',
            ],
        ];
    }

    /**
     * @dataProvider createDenormalizationContextProvider
     */
    public function testCreateDenormalizationContext(?string $resourceClass, string $operationName, array $expectedContext): void
    {
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(
            (new ResourceMetadata())
                ->withGraphql([
                    $operationName => [
                        'input' => ['class' => 'inputClass'],
                        'output' => ['class' => 'outputClass'],
                        'denormalization_context' => ['groups' => ['denormalization_group']],
                    ],
                ])
        );

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
