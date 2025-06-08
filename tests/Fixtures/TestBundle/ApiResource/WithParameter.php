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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Parameter\CustomGroupParameterProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Country;

#[Get(
    uriTemplate: 'with_parameters/{id}{._format}',
    uriVariables: [
        'id' => new Link(schema: ['type' => 'string', 'format' => 'uuid'], property: 'id'),
    ],
    parameters: [
        'groups' => new QueryParameter(filter: new GroupFilter(parameterName: 'groups', overrideDefaultGroups: false)),
        'group' => new QueryParameter(provider: [self::class, 'provideGroup']),
        'properties' => new QueryParameter(filter: 'my_dummy.property'),
        'service' => new QueryParameter(provider: CustomGroupParameterProvider::class),
        'object' => new QueryParameter(provider: new CustomGroupParameterProvider()),
        'auth' => new HeaderParameter(provider: [self::class, 'restrictAccess']),
        'priority' => new QueryParameter(provider: [self::class, 'assertSecond'], priority: 10),
        'priorityb' => new QueryParameter(provider: [self::class, 'assertFirst'], priority: 20),
        'array' => new QueryParameter(provider: [self::class, 'assertArray'], openApi: false),
    ],
    provider: [self::class, 'provide']
)]
#[GetCollection(
    uriTemplate: 'with_parameters_collection{._format}',
    parameters: [
        'hydra' => new QueryParameter(property: 'a', required: true),
    ],
    provider: [self::class, 'collectionProvider']
)]
#[GetCollection(
    uriTemplate: 'with_parameters_country{._format}',
    parameters: [
        'country' => new QueryParameter(
            schema: ['type' => 'string'],
            constraints: [new Country()],
            nativeType: new UnionType(
                new BuiltinType(TypeIdentifier::STRING),
                new CollectionType(
                    new GenericType( // @phpstan-ignore-line
                        new BuiltinType(TypeIdentifier::ARRAY), // @phpstan-ignore-line
                        new BuiltinType(TypeIdentifier::INT),
                        new BuiltinType(TypeIdentifier::STRING),
                    ),
                    true,
                ),
            )
        ),
    ],
    provider: [self::class, 'collectionProvider']
)]
#[GetCollection(
    uriTemplate: 'with_parameters_countries{._format}',
    parameters: [
        'country' => new QueryParameter(constraints: [new All([new Country()])], castToArray: true),
    ],
    provider: [self::class, 'collectionProvider'],
)]
#[GetCollection(
    uriTemplate: 'validate_parameters{._format}',
    parameters: [
        'enum' => new QueryParameter(schema: ['enum' => ['a', 'b'], 'uniqueItems' => true]),
        'num' => new QueryParameter(
            schema: ['minimum' => 1, 'maximum' => 3],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'exclusiveNum' => new QueryParameter(
            schema: ['exclusiveMinimum' => 1, 'exclusiveMaximum' => 3],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'blank' => new QueryParameter(
            openApi: new OpenApiParameter(name: 'blank', in: 'query', allowEmptyValue: false),
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'length' => new QueryParameter(
            schema: ['maxLength' => 1, 'minLength' => 3],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'array' => new QueryParameter(schema: ['minItems' => 2, 'maxItems' => 3]),
        'multipleOf' => new QueryParameter(
            schema: ['multipleOf' => 2],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'int' => new QueryParameter(
            property: 'a',
            constraints: [new Assert\Type('integer')],
            provider: [self::class, 'toInt'],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'pattern' => new QueryParameter(
            schema: ['pattern' => '\d', 'type' => 'string'],
        ),
    ],
    provider: [self::class, 'collectionProvider']
)]
#[GetCollection(
    uriTemplate: 'with_disabled_parameter_validation{._format}',
    parameters: new Parameters([new QueryParameter(key: 'bla', required: true)]),
    queryParameterValidationEnabled: false,
    provider: [self::class, 'collectionProvider']
)]
#[GetCollection(
    uriTemplate: 'with_parameters_header_and_query{._format}',
    parameters: new Parameters([
        new QueryParameter(
            key: 'q',
        ),
        new HeaderParameter(
            key: 'q',
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
    ]),
    provider: [self::class, 'headerAndQueryProvider']
)]
#[GetCollection(
    uriTemplate: 'header_required',
    parameters: [
        'Req' => new HeaderParameter(required: true, schema: ['type' => 'string']),
    ],
    provider: [self::class, 'headerProvider']
)]
#[GetCollection(
    uriTemplate: 'header_integer',
    parameters: [
        'Foo' => new HeaderParameter(
            schema: [
                'type' => 'integer',
                'example' => 3,
                'minimum' => 1,
                'maximum' => 5,
            ],
            required: true,
        ),
    ],
    provider: [self::class, 'noopProvider']
)]
#[Get(
    uriTemplate: 'with_parameters_iris',
    parameters: [
        'dummy' => new QueryParameter(provider: IriConverterParameterProvider::class),
    ],
    provider: [self::class, 'provideDummyFromParameter'],
)]
#[Get(
    uriTemplate: 'with_parameters_links',
    parameters: [
        'dummy' => new QueryParameter(provider: ReadLinkParameterProvider::class, extraProperties: ['resource_class' => Dummy::class]),
    ],
    provider: [self::class, 'provideDummyFromParameter'],
)]
#[Get(
    uriTemplate: 'with_parameters_links_no_not_found',
    parameters: [
        'dummy' => new QueryParameter(provider: ReadLinkParameterProvider::class, extraProperties: ['resource_class' => Dummy::class, 'throw_not_found' => false]),
    ],
    provider: [self::class, 'noopProvider'],
)]
#[QueryParameter(key: 'everywhere')]
class WithParameter
{
    protected static int $counter = 1;
    public int $id = 1;

    #[Groups(['a'])]
    public $a = 'foo';
    #[Groups(['b', 'custom'])]
    public $b = 'bar';

    public static function collectionProvider()
    {
        return [new self()];
    }

    public static function provide()
    {
        return new self();
    }

    public static function assertArray(): void
    {
    }

    public static function assertFirst(): void
    {
        \assert(1 === static::$counter);
        ++static::$counter;
    }

    public static function assertSecond(): void
    {
        \assert(2 === static::$counter);
    }

    public static function provideGroup(Parameter $parameter, array $parameters = [], array $context = [])
    {
        $operation = $context['operation'];

        return $operation->withNormalizationContext(['groups' => $parameters['group']]);
    }

    public static function restrictAccess(): void
    {
        throw new AccessDeniedHttpException();
    }

    public static function headerAndQueryProvider(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $parameters = $operation->getParameters();
        $values = [$parameters->get('q', HeaderParameter::class)->getValue(), $parameters->get('q', QueryParameter::class)->getValue()];

        return new JsonResponse($values);
    }

    public static function toInt(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        if (null === ($operation = $context['operation'] ?? null)) {
            return null;
        }

        $value = $parameter->getValue();

        if (is_numeric($value)) {
            $value = (int) $value;
        }

        $parameter->setValue($value);

        return $operation;
    }

    public static function headerProvider(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $parameters = $operation->getParameters();
        $values = [$parameters->get('Req', HeaderParameter::class)->getValue()];

        return new JsonResponse($values);
    }

    public static function noopProvider(Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        return new JsonResponse([]);
    }

    public static function provideDummyFromParameter(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        return $operation->getParameters()->get('dummy')->getValue();
    }
}
