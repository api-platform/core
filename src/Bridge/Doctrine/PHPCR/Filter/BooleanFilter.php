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

namespace ApiPlatform\Core\Bridge\Doctrine\PHPCR\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\AbstractBooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface as CommonQueryNameGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder as Builder;

/**
 * Filters the collection by boolean values.
 *
 * Filters collection on equality of boolean properties. The value is specified
 * as one of ( "true" | "false" | "1" | "0" ) in the query.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not one of ( "true" | "false" | "1" | "0" ) the property is ignored.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class BooleanFilter extends AbstractBooleanFilter
{
    use FilterTrait;

    /**
     * {@inheritdoc}
     *
     * @param Builder                     $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     */
    protected function filterProperty(
        string $property,
        $value,
        $queryBuilder,
        CommonQueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isBooleanField($property, $resourceClass)
        ) {
            return;
        }

        $value = $this->normalizeValue($value);
        if (null === $value) {
            return;
        }

        if ($this->isPropertyNested($property, $resourceClass)) {
            $this->addLookupsForNestedProperty($property, $queryBuilder, $resourceClass);
        }

        $queryBuilder->match()->field($property)->equals($value);
    }
}
