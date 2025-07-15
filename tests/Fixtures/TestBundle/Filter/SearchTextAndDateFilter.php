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

use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SearchTextAndDateFilter implements FilterInterface
{
    public function __construct(#[Autowire('@api_platform.doctrine.orm.search_filter.instance')] public readonly FilterInterface $searchFilter, #[Autowire('@api_platform.doctrine.orm.date_filter.instance')] public readonly FilterInterface $dateFilter, protected ?array $properties = null, private array $dateFilterProperties = [], private array $searchFilterProperties = [])
    {
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        if ($this->searchFilter instanceof PropertyAwareFilterInterface) {
            $this->searchFilter->setProperties($this->searchFilterProperties);
        }
        if ($this->dateFilter instanceof PropertyAwareFilterInterface) {
            $this->dateFilter->setProperties($this->dateFilterProperties);
        }

        return array_merge($this->searchFilter->getDescription($resourceClass), $this->dateFilter->getDescription($resourceClass));
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($this->searchFilter instanceof PropertyAwareFilterInterface) {
            $this->searchFilter->setProperties($this->searchFilterProperties);
        }
        if ($this->dateFilter instanceof PropertyAwareFilterInterface) {
            $this->dateFilter->setProperties($this->dateFilterProperties);
        }

        $this->searchFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, ['filters' => $context['filters']['searchOnTextAndDate']] + $context);
        $this->dateFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, ['filters' => $context['filters']['searchOnTextAndDate']] + $context);
    }
}
