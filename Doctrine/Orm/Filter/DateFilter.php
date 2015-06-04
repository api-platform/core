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

use Doctrine\Common\Persistence\ManagerRegistry;
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
    const EXCLUDE_NULL = true;

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

            if (isset($values[self::PARAMETER_BEFORE])) {
                $this->filterByDate($queryBuilder, $property, $values[self::PARAMETER_BEFORE], self::PARAMETER_BEFORE);
            }

            if (isset($values[self::PARAMETER_AFTER])) {
                $this->filterByDate($queryBuilder, $property, $values[self::PARAMETER_AFTER], self::PARAMETER_AFTER);
            }

            if (self::EXCLUDE_NULL === $this->properties[$property]) {
                $queryBuilder->andWhere(sprintf('o.%s IS NOT NULL', $property));
            }
        }
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
            ]
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

    /**
     * Adds date filtering in the WHERE clause.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $property
     * @param string $value
     * @param string $period
     */
    private function filterByDate(QueryBuilder $queryBuilder, $property, $value, $period)
    {
        $parameter = sprintf('date_%s_%s', $property, $period);

        $queryBuilder
            ->andWhere(sprintf('o.%s %s= :%s', $property, self::PARAMETER_BEFORE === $period ? '<' : '>', $parameter))
            ->setParameter($parameter, new \DateTime($value))
        ;
    }
}
