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

final class ODMSearchFilterValueTransformer implements FilterInterface
{
    public function __construct(#[Autowire('@api_platform.doctrine_mongodb.odm.search_filter.instance')] readonly FilterInterface $searchFilter, ?array $properties = null, private readonly ?string $key = null)
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

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $filterContext = ['filters' => $context['filters'][$this->key]] + $context;
        $this->searchFilter->apply($aggregationBuilder, $resourceClass, $operation, $filterContext);
    }
}
