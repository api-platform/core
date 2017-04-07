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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;

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
    public function getDescription(string $resourceClass): array
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
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (
            !is_array($values) ||
            !$this->isPropertyEnabled($property) ||
            !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $alias = 'o';
        $field = $property;

        if ($this->isPropertyNested($property)) {
            list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator);
        }

        foreach ($values as $operator => $value) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                $operator,
                $value
            );
        }
    }

    /**
     * Adds the where clause according to the operator.
     *
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $alias
     * @param string                      $field
     * @param string                      $operator
     * @param string                      $value
     */
    protected function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, $alias, $field, $operator, $value)
    {
        $valueParameter = $queryNameGenerator->generateParameterName($field);

        switch ($operator) {
            case self::PARAMETER_BETWEEN:
                $rangeValue = explode('..', $value);

                if (2 !== count($rangeValue)) {
                    $this->logger->notice('Invalid filter ignored', [
                        'exception' => new InvalidArgumentException(sprintf('Invalid format for "[%s]", expected "<min>..<max>"', $operator)),
                    ]);

                    return;
                }

                if (!is_numeric($rangeValue[0]) || !is_numeric($rangeValue[1])) {
                    $this->logger->notice('Invalid filter ignored', [
                        'exception' => new InvalidArgumentException(sprintf('Invalid values for "[%s]" range, expected numbers', $operator)),
                    ]);

                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%1$s.%2$s BETWEEN :%3$s_1 AND :%3$s_2', $alias, $field, $valueParameter))
                    ->setParameter(sprintf('%s_1', $valueParameter), $rangeValue[0])
                    ->setParameter(sprintf('%s_2', $valueParameter), $rangeValue[1]);

                break;
            case self::PARAMETER_GREATER_THAN:
                if (!is_numeric($value)) {
                    $this->logger->notice('Invalid filter ignored', [
                        'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected number', $operator)),
                    ]);

                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s.%s > :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                if (!is_numeric($value)) {
                    $this->logger->notice('Invalid filter ignored', [
                        'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected number', $operator)),
                    ]);

                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s.%s >= :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
            case self::PARAMETER_LESS_THAN:
                if (!is_numeric($value)) {
                    $this->logger->notice('Invalid filter ignored', [
                        'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected number', $operator)),
                    ]);

                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s.%s < :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                if (!is_numeric($value)) {
                    $this->logger->notice('Invalid filter ignored', [
                        'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected number', $operator)),
                    ]);

                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s.%s <= :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
        }
    }

    /**
     * Gets filter description.
     *
     * @param string $fieldName
     * @param string $operator
     *
     * @return array
     */
    protected function getFilterDescription(string $fieldName, string $operator): array
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
