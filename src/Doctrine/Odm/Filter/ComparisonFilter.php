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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Decorates an equality filter (ExactFilter) to add comparison operators (gt, gte, lt, lte).
 */
final class ComparisonFilter implements FilterInterface, OpenApiParameterFilterInterface, JsonSchemaFilterInterface, ManagerRegistryAwareInterface, LoggerAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use LoggerAwareTrait;
    use ManagerRegistryAwareTrait;
    use OpenApiFilterTrait;

    private const OPERATORS = [
        'gt' => 'gt',
        'gte' => 'gte',
        'lt' => 'lt',
        'lte' => 'lte',
        'ne' => 'notEqual',
    ];

    /**
     * Friendly range syntax: `?price[between]=10..100`. MongoDB has no BETWEEN keyword, so a range
     * is expressed as the native `gte`/`lte` pair on the field.
     */
    public const OPERATOR_BETWEEN = 'between';

    public function __construct(private readonly FilterInterface $filter)
    {
    }

    /**
     * @param-out array<string, mixed> $context
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        if ($this->filter instanceof ManagerRegistryAwareInterface) {
            $this->filter->setManagerRegistry($this->getManagerRegistry());
        }

        if ($this->filter instanceof LoggerAwareInterface) {
            $this->filter->setLogger($this->getLogger());
        }

        $parameter = $context['parameter'];
        $values = $parameter->getValue();

        if (!\is_array($values)) {
            return;
        }

        foreach ($values as $operator => $value) {
            if ('' === $value || null === $value) {
                continue;
            }

            if (self::OPERATOR_BETWEEN === $operator) {
                $this->applyBetween($aggregationBuilder, $resourceClass, $operation, $context, $parameter, $value);

                continue;
            }

            if (isset(self::OPERATORS[$operator])) {
                $this->applyOperator($aggregationBuilder, $resourceClass, $operation, $context, $parameter, self::OPERATORS[$operator], $value);
            }
        }
    }

    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();

        return [
            new OpenApiParameter(name: "{$key}[gt]", in: $in),
            new OpenApiParameter(name: "{$key}[gte]", in: $in),
            new OpenApiParameter(name: "{$key}[lt]", in: $in),
            new OpenApiParameter(name: "{$key}[lte]", in: $in),
            new OpenApiParameter(name: "{$key}[ne]", in: $in),
            new OpenApiParameter(name: "{$key}[between]", in: $in),
        ];
    }

    public function getSchema(Parameter $parameter): array
    {
        $innerSchema = ['type' => 'string'];
        if ($this->filter instanceof JsonSchemaFilterInterface) {
            $innerSchema = $this->filter->getSchema($parameter);
        }

        return [
            'type' => 'object',
            'properties' => [
                'gt' => $innerSchema,
                'gte' => $innerSchema,
                'lt' => $innerSchema,
                'lte' => $innerSchema,
                'ne' => $innerSchema,
                'between' => ['type' => 'string'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @param-out array<string, mixed> $context
     */
    private function applyOperator(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation, array &$context, Parameter $parameter, string $comparisonMethod, mixed $value): void
    {
        if (!\is_string($value) && !is_numeric($value) && !$value instanceof \DateTimeInterface) {
            return;
        }

        $subParameter = (clone $parameter)->setValue($value);
        $newContext = ['comparisonMethod' => $comparisonMethod, 'parameter' => $subParameter] + $context;
        $this->filter->apply($aggregationBuilder, $resourceClass, $operation, $newContext);
        if (isset($newContext['match'])) {
            $context['match'] = $newContext['match'];
        }
    }

    /**
     * @param array<string, mixed> $context
     *
     * @param-out array<string, mixed> $context
     */
    private function applyBetween(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation, array &$context, Parameter $parameter, mixed $value): void
    {
        if (!\is_string($value)) {
            return;
        }

        $bounds = explode('..', $value, 2);
        if (2 !== \count($bounds) || !is_numeric($bounds[0]) || !is_numeric($bounds[1])) {
            return;
        }

        // MongoDB range = native gte/lte pair (coerce bounds to numbers)
        $this->applyOperator($aggregationBuilder, $resourceClass, $operation, $context, $parameter, 'gte', $bounds[0] + 0);
        $this->applyOperator($aggregationBuilder, $resourceClass, $operation, $context, $parameter, 'lte', $bounds[1] + 0);
    }
}
