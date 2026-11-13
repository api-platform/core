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

namespace ApiPlatform\Laravel\Eloquent\Filter;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;

final class RangeFilter implements FilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use QueryPropertyTrait;

    private const OPERATOR_VALUE = [
        'lt' => '<',
        'gt' => '>',
        'lte' => '<=',
        'gte' => '>=',
    ];

    public function __construct(private readonly ?LoggerInterface $logger = null)
    {
    }

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        $values = \is_array($values) ? array_intersect_key($values, self::OPERATOR_VALUE) : [];
        if (!$values) {
            $this->logger?->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(\sprintf('At least one valid operator ("%s") is required for "%s" parameter', implode('", "', array_keys(self::OPERATOR_VALUE)), $parameter->getKey())),
            ]);

            return $builder;
        }

        $queryProperty = $this->getQueryProperty($parameter);

        foreach ($values as $key => $value) {
            $builder = $builder->{$context['whereClause'] ?? 'where'}($queryProperty, self::OPERATOR_VALUE[$key], $value);
        }

        return $builder;
    }

    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'number'];
    }

    /**
     * @return OpenApiParameter[]
     */
    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();

        return [
            new OpenApiParameter(name: $key.'[gt]', in: $in),
            new OpenApiParameter(name: $key.'[lt]', in: $in),
            new OpenApiParameter(name: $key.'[gte]', in: $in),
            new OpenApiParameter(name: $key.'[lte]', in: $in),
        ];
    }
}
