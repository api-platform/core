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
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Filters the collection by range.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 */
class RangeFilter extends AbstractFilter
{
    const PARAMETER_BETWEEN = 'between';
    const PARAMETER_GREATER_THAN = 'gt';
    const PARAMETER_GREATER_THAN_OR_EQUAL = 'gte';
    const PARAMETER_LESS_THAN = 'lt';
    const PARAMETER_LESS_THAN_OR_EQUAL = 'lte';

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        foreach ($this->extractProperties($request) as $property => $values) {
            if (
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

            foreach ($values as $operator => $value) {
                $this->addWhere(
                    $queryBuilder,
                    $alias,
                    $field,
                    $operator,
                    $value
                );
            }
        }
    }

    /**
     * Adds the where clause according to the operator.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $field
     * @param string       $operator
     * @param string       $value
     */
    private function addWhere(QueryBuilder $queryBuilder, $alias, $field, $operator, $value)
    {
        $valueParameter = QueryNameGenerator::generateParameterName(sprintf('%s_%s', $field, $operator));

        switch ($operator) {
            case self::PARAMETER_BETWEEN:
                $rangeValue = explode('..', $value);

                if (2 !== count($rangeValue)) {
                    throw new InvalidArgumentException(sprintf('Invalid format for [%s], expected to be <min>..<max>', $operator));
                }

                return $queryBuilder
                    ->andWhere(sprintf('%1$s.%2$s BETWEEN :%3$s_1 AND :%3$s_2', $alias, $field, $valueParameter))
                    ->setParameter(sprintf('%s_1', $valueParameter), $rangeValue[0])
                    ->setParameter(sprintf('%s_2', $valueParameter), $rangeValue[1]);

            case self::PARAMETER_GREATER_THAN:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s > :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s >= :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

            case self::PARAMETER_LESS_THAN:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s < :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s <= :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
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

        foreach ($properties as $property => $operator) {
            if (!$this->isPropertyMapped($property, $resource)) {
                continue;
            }

            $description += $this->getFilterDescription($property, self::PARAMETER_BETWEEN);
            $description += $this->getFilterDescription($property, self::PARAMETER_GREATER_THAN);
            $description += $this->getFilterDescription($property, self::PARAMETER_GREATER_THAN_OR_EQUAL);
            $description += $this->getFilterDescription($property, self::PARAMETER_LESS_THAN);
            $description += $this->getFilterDescription($property, self::PARAMETER_LESS_THAN_OR_EQUAL);
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
                'type' => 'string',
                'required' => false,
            ],
        ];
    }
}
