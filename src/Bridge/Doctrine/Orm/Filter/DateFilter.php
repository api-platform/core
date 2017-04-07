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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Filters the collection by date intervals.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class DateFilter extends AbstractFilter
{
    const PARAMETER_BEFORE = 'before';
    const PARAMETER_AFTER = 'after';
    const EXCLUDE_NULL = 'exclude_null';
    const INCLUDE_NULL_BEFORE = 'include_null_before';
    const INCLUDE_NULL_AFTER = 'include_null_after';
    const DOCTRINE_DATE_TYPES = [
        'date' => true,
        'datetime' => true,
        'datetimetz' => true,
        'time' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $nullManagement) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isDateField($property, $resourceClass)) {
                continue;
            }

            $description += $this->getFilterDescription($property, self::PARAMETER_BEFORE);
            $description += $this->getFilterDescription($property, self::PARAMETER_AFTER);
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        // Expect $values to be an array having the period as keys and the date value as values
        if (
            !is_array($values) ||
            !$this->isPropertyEnabled($property) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isDateField($property, $resourceClass)
        ) {
            return;
        }

        $alias = 'o';
        $field = $property;

        if ($this->isPropertyNested($property)) {
            list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator);
        }

        $nullManagement = $this->properties[$property] ?? null;

        if (self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
        }

        if (isset($values[self::PARAMETER_BEFORE])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_BEFORE,
                $values[self::PARAMETER_BEFORE],
                $nullManagement
            );
        }

        if (isset($values[self::PARAMETER_AFTER])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_AFTER,
                $values[self::PARAMETER_AFTER],
                $nullManagement
            );
        }
    }

    /**
     * Adds the where clause according to the chosen null management.
     *
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $alias
     * @param string                      $field
     * @param string                      $operator
     * @param string                      $value
     * @param string|null                 $nullManagement
     */
    protected function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, string $operator, string $value, string $nullManagement = null)
    {
        $valueParameter = $queryNameGenerator->generateParameterName($field);
        $baseWhere = sprintf('%s.%s %s :%s', $alias, $field, self::PARAMETER_BEFORE === $operator ? '<=' : '>=', $valueParameter);

        if (null === $nullManagement || self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($baseWhere);
        } elseif (
            (self::PARAMETER_BEFORE === $operator && self::INCLUDE_NULL_BEFORE === $nullManagement) ||
            (self::PARAMETER_AFTER === $operator && self::INCLUDE_NULL_AFTER === $nullManagement)
        ) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $baseWhere,
                $queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field))
            ));
        } else {
            $queryBuilder->andWhere($queryBuilder->expr()->andX(
                $baseWhere,
                $queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field))
            ));
        }

        $queryBuilder->setParameter($valueParameter, new \DateTime($value));
    }

    /**
     * Determines whether the given property refers to a date field.
     *
     * @param string $property
     * @param string $resourceClass
     *
     * @return bool
     */
    protected function isDateField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return isset(self::DOCTRINE_DATE_TYPES[$metadata->getTypeOfField($propertyParts['field'])]);
    }

    /**
     * Gets filter description.
     *
     * @param string $property
     * @param string $period
     *
     * @return array
     */
    protected function getFilterDescription(string $property, string $period): array
    {
        return [
            sprintf('%s[%s]', $property, $period) => [
                'property' => $property,
                'type' => \DateTimeInterface::class,
                'required' => false,
            ],
        ];
    }
}
