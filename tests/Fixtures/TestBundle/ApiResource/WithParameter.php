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
use ApiPlatform\Tests\Fixtures\TestBundle\Parameter\CustomGroupParameterProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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
    uriTemplate: 'validate_parameters{._format}',
    parameters: [
        'enum' => new QueryParameter(schema: ['enum' => ['a', 'b'], 'uniqueItems' => true]),
        'num' => new QueryParameter(schema: ['minimum' => 1, 'maximum' => 3]),
        'exclusiveNum' => new QueryParameter(schema: ['exclusiveMinimum' => 1, 'exclusiveMaximum' => 3]),
        'blank' => new QueryParameter(openApi: new OpenApiParameter(name: 'blank', in: 'query', allowEmptyValue: false)),
        'length' => new QueryParameter(schema: ['maxLength' => 1, 'minLength' => 3]),
        'array' => new QueryParameter(schema: ['minItems' => 2, 'maxItems' => 3]),
        'multipleOf' => new QueryParameter(schema: ['multipleOf' => 2]),
        'int' => new QueryParameter(property: 'a', constraints: [new Assert\Type('integer')], provider: [self::class, 'toInt']),
        'pattern' => new QueryParameter(schema: ['pattern' => '\d']),
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
    parameters: new Parameters([new QueryParameter(key: 'q'), new HeaderParameter(key: 'q')]),
    provider: [self::class, 'headerAndQueryProvider']
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

    public static function headerAndQueryProvider(Operation $operation, array $uriVariables = [], array $context = [])
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

        $parameters = $operation->getParameters();
        $parameters->add($parameter->getKey(), $parameter = $parameter->withExtraProperties(
            $parameter->getExtraProperties() + ['_api_values' => $value]
        ));

        return $operation->withParameters($parameters);
    }
}
