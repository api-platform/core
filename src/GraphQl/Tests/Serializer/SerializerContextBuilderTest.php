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

namespace ApiPlatform\GraphQl\Tests\Serializer;

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilder;
use ApiPlatform\GraphQl\Tests\Fixtures\Serializer\NameConverter\CustomConverter;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SerializerContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    private SerializerContextBuilder $serializerContextBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->serializerContextBuilder = $this->buildSerializerContextBuilder();
    }

    private function buildSerializerContextBuilder(?AdvancedNameConverterInterface $advancedNameConverter = null): SerializerContextBuilder
    {
        return new SerializerContextBuilder($advancedNameConverter ?? new CustomConverter());
    }

    private function buildOperationFromContext(bool $isMutation, bool $isSubscription, array $expectedContext, bool $isNormalization = true, ?string $resourceClass = null): Operation
    {
        $operation = !$isMutation && !$isSubscription ? new Query() : new Mutation();
        if ($isSubscription) {
            $operation = new Subscription();
        }

        $operation = $operation->withShortName('shortName');
        if (isset($expectedContext['operation_name'])) {
            $operation = $operation->withName($expectedContext['operation_name']);
        }

        if ($resourceClass) {
            if (isset($expectedContext['groups'])) {
                if ($isNormalization) {
                    $operation = $operation->withNormalizationContext(['groups' => $expectedContext['groups']]);
                } else {
                    $operation = $operation->withDenormalizationContext(['groups' => $expectedContext['groups']]);
                }
            }

            if (isset($expectedContext['input'])) {
                $operation = $operation->withInput($expectedContext['input']);
            }

            if (isset($expectedContext['input'])) {
                $operation = $operation->withOutput($expectedContext['output']);
            }
        }

        \assert($operation instanceof Operation);

        return $operation;
    }

    /**
     * @dataProvider createNormalizationContextProvider
     */
    public function testCreateNormalizationContext(?string $resourceClass, string $operationName, array $fields, bool $isMutation, bool $isSubscription, bool $noInfo, array $expectedContext, ?callable $advancedNameConverter = null, ?string $expectedExceptionClass = null, ?string $expectedExceptionMessage = null): void
    {
        $resolverContext = [];

        $operation = $this->buildOperationFromContext($isMutation, $isSubscription, $expectedContext, true, $resourceClass);
        if ($noInfo) {
            $resolverContext['fields'] = $fields;
        } else {
            $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
            $resolveInfoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);
            $resolverContext['info'] = $resolveInfoProphecy->reveal();
        }

        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $serializerContextBuilder = $this->serializerContextBuilder;
        if ($advancedNameConverter) {
            $serializerContextBuilder = $this->buildSerializerContextBuilder($advancedNameConverter($this));
        }

        $context = $serializerContextBuilder->create($resourceClass, $operation, $resolverContext, true);

        unset($context['operation']);
        $this->assertEquals($expectedContext, $context);
    }

    public static function createNormalizationContextProvider(): iterable
    {
        $advancedNameConverterFactory = function (self $that): AdvancedNameConverterInterface {
            $advancedNameConverterProphecy = $that->prophesize(AdvancedNameConverterInterface::class);
            $advancedNameConverterProphecy->denormalize('field', 'myResource', null, Argument::type('array'))->willReturn('denormalizedField');

            return $advancedNameConverterProphecy->reveal();
        };

        yield 'nominal' => [
            $resourceClass = 'myResource',
            $operationName = 'item_query',
            ['_id' => 3, 'field' => 'foo'],
            false,
            false,
            false,
            [
                'groups' => ['normalization_group'],
                'resource_class' => $resourceClass,
                'operation_name' => $operationName,
                'graphql_operation_name' => $operationName,
                'input' => ['class' => 'inputClass'],
                'output' => ['class' => 'outputClass'],
                'attributes' => [
                    'id' => 3,
                    'field' => 'foo',
                ],
            ],
        ];
        yield 'nominal with advanced name converter' => [
            $resourceClass = 'myResource',
            $operationName = 'item_query',
            ['_id' => 3, 'field' => 'foo'],
            false,
            false,
            false,
            [
                'groups' => ['normalization_group'],
                'resource_class' => $resourceClass,
                'operation_name' => $operationName,
                'graphql_operation_name' => $operationName,
                'input' => ['class' => 'inputClass'],
                'output' => ['class' => 'outputClass'],
                'attributes' => [
                    'id' => 3,
                    'denormalizedField' => 'foo',
                ],
            ],
            $advancedNameConverterFactory,
        ];
        yield 'nominal collection' => [
            $resourceClass = 'myResource',
            $operationName = 'collection_query',
            ['edges' => ['node' => ['nodeField' => 'baz']]],
            false,
            false,
            false,
            [
                'groups' => ['normalization_group'],
                'resource_class' => $resourceClass,
                'operation_name' => $operationName,
                'graphql_operation_name' => $operationName,
                'input' => ['class' => 'inputClass'],
                'output' => ['class' => 'outputClass'],
                'attributes' => [
                    'nodeField' => 'baz',
                ],
            ],
        ];
        yield 'no resource class' => [
            $resourceClass = null,
            $operationName = 'item_query',
            ['related' => ['_id' => 9]],
            false,
            false,
            false,
            [
                'resource_class' => $resourceClass,
                'operation_name' => $operationName,
                'graphql_operation_name' => $operationName,
                'attributes' => [
                    'related' => ['id' => 9],
                ],
            ],
        ];
        yield 'mutation' => [
            $resourceClass = 'myResource',
            $operationName = 'create',
            ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
            true,
            false,
            false,
            [
                'groups' => ['normalization_group'],
                'resource_class' => $resourceClass,
                'operation_name' => $operationName,
                'graphql_operation_name' => $operationName,
                'input' => ['class' => 'inputClass'],
                'output' => ['class' => 'outputClass'],
                'attributes' => [
                    'id' => 7,
                    'related' => ['field' => 'bar'],
                ],
            ],
        ];
        yield 'subscription (using fields in context)' => [
            $resourceClass = 'myResource',
            $operationName = 'update',
            ['shortName' => ['_id' => 7, 'related' => ['field' => 'bar']]],
            false,
            true,
            true,
            [
                'groups' => ['normalization_group'],
                'resource_class' => $resourceClass,
                'operation_name' => $operationName,
                'graphql_operation_name' => $operationName,
                'no_resolver_data' => true,
                'input' => ['class' => 'inputClass'],
                'output' => ['class' => 'outputClass'],
                'attributes' => [
                    'id' => 7,
                    'related' => ['field' => 'bar'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createDenormalizationContextProvider
     */
    public function testCreateDenormalizationContext(?string $resourceClass, string $operationName, array $expectedContext): void
    {
        $operation = $this->buildOperationFromContext(true, false, $expectedContext, false, $resourceClass);

        $context = $this->serializerContextBuilder->create($resourceClass, $operation, [], false);

        unset($context['operation']);
        $this->assertEquals($expectedContext, $context);
    }

    public static function createDenormalizationContextProvider(): array
    {
        return [
            'nominal' => [
                $resourceClass = 'myResource',
                $operationName = 'item_query',
                [
                    'groups' => ['denormalization_group'],
                    'resource_class' => $resourceClass,
                    'operation_name' => $operationName,
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
                    'operation_name' => $operationName,
                    'graphql_operation_name' => $operationName,
                ],
            ],
        ];
    }
}
