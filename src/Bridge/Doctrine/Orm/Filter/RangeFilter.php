<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Builder\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Builder\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

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

    private $requestStack;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param RequestStack    $requestStack
     * @param array|null      $properties
     */
    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, array $properties = null)
    {
        parent::__construct($managerRegistry, $properties);

        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        foreach ($this->extractProperties($request) as $property => $values) {
            if (
                !is_array($values) ||
                !$this->isPropertyEnabled($property) ||
                !$this->isPropertyMapped($property, $resourceClass)
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

                $queryBuilder
                    ->andWhere(sprintf('%1$s.%2$s BETWEEN :%3$s_1 AND :%3$s_2', $alias, $field, $valueParameter))
                    ->setParameter(sprintf('%s_1', $valueParameter), $rangeValue[0])
                    ->setParameter(sprintf('%s_2', $valueParameter), $rangeValue[1]);

                break;

            case self::PARAMETER_GREATER_THAN:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s > :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;

            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s >= :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;

            case self::PARAMETER_LESS_THAN:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s < :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;

            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s <= :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass) : array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass)) {
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
     * @param string $operator
     *
     * @return array
     */
    private function getFilterDescription(string $fieldName, string $operator) : array
    {
        return [
            sprintf('%s[%s]', $fieldName, $operator) => [
                'property' => $fieldName,
                'type' => 'string',
                'required' => false,
            ],
        ];
    }
}
