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
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ORM\QueryBuilder;

/**
 * Decorates an equality filter (ExactFilter, UuidFilter) to add comparison operators (gt, gte, lt, lte, between).
 *
 * @experimental
 */
final class ComparisonFilter implements FilterInterface, OpenApiParameterFilterInterface, ManagerRegistryAwareInterface, LoggerAwareInterface
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
    ];

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
                $subParameter = (clone $parameter)->setValue($value);
                $this->filter->apply(
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass,
                    $operation,
                    ['operator' => self::OPERATORS[$operator], 'parameter' => $subParameter] + $context
                );
                continue;
            }

            if ('between' === $operator) {
                $range = explode('..', (string) $value, 2);
                if (2 !== \count($range)) {
                    continue;
                }

                if ($range[0] === $range[1]) {
                    $subParameter = (clone $parameter)->setValue($range[0]);
                    $this->filter->apply(
                        $queryBuilder,
                        $queryNameGenerator,
                        $resourceClass,
                        $operation,
                        ['parameter' => $subParameter] + $context
                    );
                } else {
                    $subParameter = (clone $parameter)->setValue($range[0]);
                    $this->filter->apply(
                        $queryBuilder,
                        $queryNameGenerator,
                        $resourceClass,
                        $operation,
                        ['operator' => '>=', 'parameter' => $subParameter] + $context
                    );

                    $subParameter = (clone $parameter)->setValue($range[1]);
                    $this->filter->apply(
                        $queryBuilder,
                        $queryNameGenerator,
                        $resourceClass,
                        $operation,
                        ['operator' => '<=', 'parameter' => $subParameter] + $context
                    );
                }
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
            new OpenApiParameter(name: "{$key}[between]", in: $in),
        ];
    }
}
