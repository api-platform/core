<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Filter;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Util\QueryUtils;
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
        $fieldNames = $this->getDateFieldNames($resource);

        foreach ($this->extractProperties($request) as $property => $values) {
            // Expect $values to be an array having the period as keys and the date value as values
            if (
                !isset($fieldNames[$property]) ||
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
                    $alias = QueryUtils::generateJoinAlias($association);
                    $queryBuilder->join(sprintf('%s.%s', $parentAlias, $association), $alias);
                    $parentAlias = $alias;
                }

                $field = $propertyParts['field'];
            }

            $nullManagement = isset($this->properties[$property]) ? $this->properties[$property] : null;

            if (self::EXCLUDE_NULL === $nullManagement) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
            }

            if (isset($values[self::PARAMETER_BEFORE])) {
                $this->addWhere(
                    $queryBuilder,
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
        $valueParameter = QueryUtils::generateParameterName(sprintf('%s_%s', $field, $operator));
        $baseWhere = sprintf('%s.%s %s :%s', $alias, $field, self::PARAMETER_BEFORE === $operator ? '<=' : '>=', $valueParameter);

        if (null === $nullManagement || self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($baseWhere);
        } elseif (
            (self::PARAMETER_BEFORE === $operator && self::INCLUDE_NULL_BEFORE === $nullManagement)
            ||
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
     * {@inheritdoc}
     */
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];
        foreach ($this->getClassMetadata($resource)->getFieldNames() as $fieldName) {
            if ($this->isPropertyEnabled($fieldName)) {
                $description += $this->getFilterDescription($fieldName, self::PARAMETER_BEFORE);
                $description += $this->getFilterDescription($fieldName, self::PARAMETER_AFTER);
            }
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
     * Gets names of fields with a date type.
     *
     * @param ResourceInterface $resource
     *
     * @return array
     */
    private function getDateFieldNames(ResourceInterface $resource)
    {
        $classMetadata = $this->getClassMetadata($resource);
        $dateFieldNames = [];

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if (isset(self::$doctrineDateTypes[$classMetadata->getTypeOfField($fieldName)])) {
                $dateFieldNames[$fieldName] = true;
            }
        }

        return $dateFieldNames;
    }
}
