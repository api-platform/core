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
use Symfony\Component\HttpFoundation\Request;

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
            if (!isset($fieldNames[$property]) || !is_array($values) || !$this->isPropertyEnabled($property)) {
                continue;
            }

            $nullManagement = isset($this->properties[$property]) ? $this->properties[$property] : null;

            if (self::EXCLUDE_NULL === $nullManagement) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(sprintf('o.%s', $property)));
            }

            if (isset($values[self::PARAMETER_BEFORE])) {
                $this->addWhere(
                    $queryBuilder,
                    $property,
                    self::PARAMETER_BEFORE,
                    $values[self::PARAMETER_BEFORE],
                    $nullManagement
                );
            }

            if (isset($values[self::PARAMETER_AFTER])) {
                $this->addWhere(
                    $queryBuilder,
                    $property,
                    self::PARAMETER_AFTER,
                    $values[self::PARAMETER_AFTER],
                    $nullManagement
                );
            }
        }
    }

    /**
     * Adds the where clause accordingly to the choosed null management.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $property
     * @param string       $parameter
     * @param string       $value
     * @param int|null     $nullManagement
     */
    private function addWhere(QueryBuilder $queryBuilder, $property, $parameter, $value, $nullManagement)
    {
        $queryParameter = sprintf('date_%s_%s', $parameter, $property);
        $where = sprintf('o.%s %s= :%s', $property, self::PARAMETER_BEFORE === $parameter ? '<' : '>', $queryParameter);

        $queryBuilder->setParameter($queryParameter, new \DateTime($value));

        if (null === $nullManagement || self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($where);

            return;
        }

        if (
            (self::PARAMETER_BEFORE === $parameter && self::INCLUDE_NULL_BEFORE === $nullManagement)
            ||
            (self::PARAMETER_AFTER === $parameter && self::INCLUDE_NULL_AFTER === $nullManagement)
        ) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $where,
                $queryBuilder->expr()->isNull(sprintf('o.%s', $property))
            ));

            return;
        }

        $queryBuilder->andWhere($queryBuilder->expr()->andX(
            $where,
            $queryBuilder->expr()->isNotNull(sprintf('o.%s', $property))
        ));
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
