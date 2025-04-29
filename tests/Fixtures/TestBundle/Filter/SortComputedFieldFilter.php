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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterNotFound;
use Doctrine\ORM\QueryBuilder;

class SortComputedFieldFilter implements FilterInterface, JsonSchemaFilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [];
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($context['parameter']->getValue() instanceof ParameterNotFound) {
            return;
        }

        $queryBuilder->addOrderBy('totalQuantity', $context['parameter']->getValue()['totalQuantity'] ?? 'ASC');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['asc', 'desc']];
    }
}
