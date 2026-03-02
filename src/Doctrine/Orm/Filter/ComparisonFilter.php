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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ORM\QueryBuilder;

/**
 * Decorates an equality filter (ExactFilter, UuidFilter) to add comparison operators (gt, gte, lt, lte).
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
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'ne' => '!=',
    ];

    public const ALLOWED_DQL_OPERATORS = ['=', '>', '>=', '<', '<=', '!=', '<>'];

    public function __construct(private readonly FilterInterface $filter)
    {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
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
                $this->applyOperator($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context, $parameter, self::OPERATORS[$operator], $value);
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
            ],
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    private function applyOperator(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation, array $context, Parameter $parameter, string $operator, mixed $value): void
    {
        if (!\is_string($value) && !is_numeric($value) && !$value instanceof \DateTimeInterface) {
            return;
        }

        $subParameter = (clone $parameter)->setValue($value);
        $this->filter->apply(
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            $operation,
            ['operator' => $operator, 'parameter' => $subParameter] + $context
        );
    }
}
