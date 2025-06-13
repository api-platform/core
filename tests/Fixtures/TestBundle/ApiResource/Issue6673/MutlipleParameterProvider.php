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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6673;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[GetCollection(
    uriTemplate: 'issue6673_multiple_parameter_provider',
    shortName: 'multiple_parameter_provider',
    outputFormats: ['json'],
    parameters: [
        'a' => new QueryParameter(
            provider: [self::class, 'parameterOneProvider'],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
        'b' => new QueryParameter(
            provider: [self::class, 'parameterTwoProvider'],
            nativeType: new BuiltinType(TypeIdentifier::STRING),
        ),
    ],
    provider: [self::class, 'provide']
)]
final class MutlipleParameterProvider
{
    public function __construct(public readonly string $id)
    {
    }

    public static function provide(Operation $operation): ?array
    {
        return $operation->getNormalizationContext();
    }

    public static function parameterOneProvider(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'];
        $context = $operation->getNormalizationContext() ?? [];
        $context['a'] = $parameter->getValue();

        return $operation->withNormalizationContext($context);
    }

    public static function parameterTwoProvider(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'];
        $context = $operation->getNormalizationContext() ?? [];
        $context['b'] = $parameter->getValue();

        return $operation->withNormalizationContext($context);
    }
}
