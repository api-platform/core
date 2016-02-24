<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Filter;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Util\QueryNameGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Filters the collection by date intervals.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class DateFilter extends AbstractFilter
{
    const PARAMETER_BEFORE = 'before';
    const PARAMETER_AFTER = 'after';
    const EXCLUDE_NULL = 0;
    const INCLUDE_NULL_BEFORE = 1;
    const INCLUDE_NULL_AFTER = 2;

    /**
     * @var array
     */
    private static $doctrineDateTypes = [
        'date' => true,
        'datetime' => true,
        'datetimetz' => true,
        'time' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        foreach ($this->extractProperties($request) as $property => $values) {
            // Expect $values to be an array having the period as keys and the date value as values
            if (
                !$this->isDateField($property, $resource) ||
                !is_array($values) ||
                !$this->isPropertyEnabled($property) ||
                !$this->isPropertyMapped($property, $resource)
            ) {
                continue;
            }

            $alias = 'o';
            $field = $property;

            if ($this->isPropertyNested($property)) {
                $propertyParts = $this->splitPropertyParts($property);

                $parentAlias = $alias;

                foreach ($propertyParts['associations'] as $association) {
                    $alias = QueryNameGenerator::generateJoinAlias($association);
                    $queryBuilder->join(sprintf('%s.%s', $parentAlias, $association), $alias);
                    $parentAlias = $alias;
                }

                $field = $propertyParts['field'];
            }

            $nullManagement = isset($this->properties[$property]) ? $this->properties[$property] : null;

            if (!empty($values[self::PARAMETER_BEFORE])) {
                $this->addWhere(
                    $queryBuilder,
                    $alias,
                    $field,
                    self::PARAMETER_BEFORE,
                    $values[self::PARAMETER_BEFORE],
                    $nullManagement
                );
            }

            if (!empty($values[self::PARAMETER_AFTER])) {
                $this->addWhere(
                    $queryBuilder,
                    $alias,
                    $field,
                    self::PARAMETER_AFTER,
                    $values[self::PARAMETER_AFTER],
                    $nullManagement
                );
            }
        }
    }

    /**
     * Adds the where clause according to the chosen null management.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $field
     * @param string       $operator
     * @param string       $value
     * @param int|null     $nullManagement
     */
    private function addWhere(QueryBuilder $queryBuilder, $alias, $field, $operator, $value, $nullManagement)
    {
        $valueParameter = QueryNameGenerator::generateParameterName(sprintf('%s_%s', $field, $operator));
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

        if (self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resource)->getFieldNames(), null);
        }

        foreach ($properties as $property => $nullManagement) {
            if (!$this->isPropertyMapped($property, $resource) || !$this->isDateField($property, $resource)) {
                continue;
            }

            $description += $this->getFilterDescription($property, self::PARAMETER_BEFORE);
            $description += $this->getFilterDescription($property, self::PARAMETER_AFTER);
        }

        return $description;
    }

    /**
     * Gets filter description.
     *
     * @param string $fieldName
     * @param string $period
     *
     * @return array
     */
    private function getFilterDescription($fieldName, $period)
    {
        return [
            sprintf('%s[%s]', $fieldName, $period) => [
                'property' => $fieldName,
                'type' => '\DateTime',
                'required' => false,
            ],
        ];
    }

    /**
     * Determines whether the given property refers to a date field.
     *
     * @param string            $property
     * @param ResourceInterface $resource
     *
     * @return array
     */
    private function isDateField($property, ResourceInterface $resource)
    {
        $propertyParts = $this->splitPropertyParts($property);
        $metadata = $this->getNestedMetadata($resource, $propertyParts['associations']);

        return isset(self::$doctrineDateTypes[$metadata->getTypeOfField($propertyParts['field'])]);
    }
}
