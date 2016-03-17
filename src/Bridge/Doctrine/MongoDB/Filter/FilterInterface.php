<?php
namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Filter;

use ApiPlatform\Core\Api\FilterInterface as BaseFilterInterface;
use Doctrine\MongoDB\Query\Builder;

/**
 * Doctrine MongoDB ODM filter interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * Applies the filter.
     *
     * @param Builder $queryBuilder
     * @param string       $resourceClass
     * @param string|null  $operationName
     */
    public function apply(Builder $queryBuilder, string $resourceClass, string $operationName = null);
}
