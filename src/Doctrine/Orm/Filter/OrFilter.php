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

use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class OrFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use OpenApiFilterTrait;

    /**
     * @param array<FilterInterface> $filters
     */
    public function __construct(private readonly array $filters)
    {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        foreach ($this->filters as $filter) {
            $filter->apply(
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass,
                $operation,
                ['whereClause' => 'orWhere'] + $context
            );
        }
    }
}
