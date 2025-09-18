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
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ODMSearchTextAndDateFilter implements FilterInterface
{
    public function __construct(#[Autowire('@api_platform.doctrine_mongodb.odm.search_filter.instance')] public readonly FilterInterface $searchFilter, #[Autowire('@api_platform.doctrine_mongodb.odm.date_filter.instance')] public readonly FilterInterface $dateFilter, protected ?array $properties = null, array $dateFilterProperties = [], array $searchFilterProperties = [])
    {
        if ($searchFilter instanceof PropertyAwareFilterInterface) {
            $searchFilter->setProperties($searchFilterProperties);
        }
        if ($dateFilter instanceof PropertyAwareFilterInterface) {
            $dateFilter->setProperties($dateFilterProperties);
        }
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        return array_merge($this->searchFilter->getDescription($resourceClass), $this->dateFilter->getDescription($resourceClass));
    }

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $filterContext = ['filters' => $context['filters']['searchOnTextAndDate']] + $context;
        $this->searchFilter->apply($aggregationBuilder, $resourceClass, $operation, $filterContext);
        $this->dateFilter->apply($aggregationBuilder, $resourceClass, $operation, $filterContext);
    }
}
