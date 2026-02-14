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
 *
 * @experimental
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
    ];

    public const ALLOWED_COMPARISON_METHODS = ['equals', 'gt', 'gte', 'lt', 'lte'];

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

            if (isset(self::OPERATORS[$operator])) {
                $this->applyOperator($aggregationBuilder, $resourceClass, $operation, $context, $parameter, self::OPERATORS[$operator], $value);
            }
        }
    }

    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();
        $schema = $this->getInnerSchema($parameter);

        return [
            new OpenApiParameter(name: "{$key}[gt]", in: $in, schema: $schema),
            new OpenApiParameter(name: "{$key}[gte]", in: $in, schema: $schema),
            new OpenApiParameter(name: "{$key}[lt]", in: $in, schema: $schema),
            new OpenApiParameter(name: "{$key}[lte]", in: $in, schema: $schema),
        ];
    }

    public function getSchema(Parameter $parameter): array
    {
        $innerSchema = $this->getInnerSchema($parameter);

        return [
            'type' => 'object',
            'properties' => [
                'gt' => $innerSchema,
                'gte' => $innerSchema,
                'lt' => $innerSchema,
                'lte' => $innerSchema,
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

    private function getInnerSchema(Parameter $parameter): array
    {
        if ($this->filter instanceof JsonSchemaFilterInterface) {
            return $this->filter->getSchema($parameter);
        }

        return ['type' => 'string'];
    }
}
