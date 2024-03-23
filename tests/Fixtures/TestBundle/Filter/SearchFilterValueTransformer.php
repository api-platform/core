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
use ApiPlatform\Doctrine\Orm\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SearchFilterValueTransformer implements FilterInterface
{
    public function __construct(#[Autowire('@api_platform.doctrine.orm.search_filter.instance')] readonly FilterInterface $searchFilter, ?array $properties = null, private readonly ?string $key = null)
    {
        if ($searchFilter instanceof PropertyAwareFilterInterface) {
            $searchFilter->setProperties($properties);
        }
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        return $this->searchFilter->getDescription($resourceClass);
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->searchFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, ['filters' => $context['filters'][$this->key]] + $context);
    }
}
