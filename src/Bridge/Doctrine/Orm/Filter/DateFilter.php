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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

/**
 * Filters the collection by date intervals.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class DateFilter extends AbstractContextAwareFilter
{
    const PARAMETER_BEFORE = 'before';
    const PARAMETER_STRICTLY_BEFORE = 'strictly_before';
    const PARAMETER_AFTER = 'after';
    const PARAMETER_STRICTLY_AFTER = 'strictly_after';
    const EXCLUDE_NULL = 'exclude_null';
    const INCLUDE_NULL_BEFORE = 'include_null_before';
    const INCLUDE_NULL_AFTER = 'include_null_after';
    const INCLUDE_NULL_BEFORE_AND_AFTER = 'include_null_before_and_after';
    const DOCTRINE_DATE_TYPES = [
        'date' => true,
        'datetime' => true,
        'datetimetz' => true,
        'time' => true,
        'date_immutable' => true,
        'datetime_immutable' => true,
        'datetimetz_immutable' => true,
        'time_immutable' => true,
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
            $description += $this->getFilterDescription($property, self::PARAMETER_STRICTLY_BEFORE);
            $description += $this->getFilterDescription($property, self::PARAMETER_AFTER);
            $description += $this->getFilterDescription($property, self::PARAMETER_STRICTLY_AFTER);
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
            !\is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isDateField($property, $resourceClass)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $nullManagement = $this->properties[$property] ?? null;
        $type = $this->getDoctrineFieldType($property, $resourceClass);

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
                $nullManagement,
                $type
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_BEFORE])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_STRICTLY_BEFORE,
                $values[self::PARAMETER_STRICTLY_BEFORE],
                $nullManagement,
                $type
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
                $nullManagement,
                $type
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_AFTER])) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                self::PARAMETER_STRICTLY_AFTER,
                $values[self::PARAMETER_STRICTLY_AFTER],
                $nullManagement,
                $type
            );
        }
    }

    /**
     * Adds the where clause according to the chosen null management.
     *
     * @param string|Type $type
     */
    protected function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, string $operator, string $value, string $nullManagement = null, $type = null)
    {
        try {
            $value = false === strpos($type, '_immutable') ? new \DateTime($value) : new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            // Silently ignore this filter if it can not be transformed to a \DateTime
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('The field "%s" has a wrong date format. Use one accepted by the \DateTime constructor', $field)),
            ]);

            return;
        }

        $valueParameter = $queryNameGenerator->generateParameterName($field);
        $operatorValue = [
            self::PARAMETER_BEFORE => '<=',
            self::PARAMETER_STRICTLY_BEFORE => '<',
            self::PARAMETER_AFTER => '>=',
            self::PARAMETER_STRICTLY_AFTER => '>',
        ];
        $baseWhere = sprintf('%s.%s %s :%s', $alias, $field, $operatorValue[$operator], $valueParameter);

        if (null === $nullManagement || self::EXCLUDE_NULL === $nullManagement) {
            $queryBuilder->andWhere($baseWhere);
        } elseif (
            (self::INCLUDE_NULL_BEFORE === $nullManagement && \in_array($operator, [self::PARAMETER_BEFORE, self::PARAMETER_STRICTLY_BEFORE], true)) ||
            (self::INCLUDE_NULL_AFTER === $nullManagement && \in_array($operator, [self::PARAMETER_AFTER, self::PARAMETER_STRICTLY_AFTER], true)) ||
            (self::INCLUDE_NULL_BEFORE_AND_AFTER === $nullManagement && \in_array($operator, [self::PARAMETER_AFTER, self::PARAMETER_STRICTLY_AFTER, self::PARAMETER_BEFORE, self::PARAMETER_STRICTLY_BEFORE], true))
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

        $queryBuilder->setParameter($valueParameter, $value, $type);
    }

    /**
     * Determines whether the given property refers to a date field.
     */
    protected function isDateField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_DATE_TYPES[$this->getDoctrineFieldType($property, $resourceClass)]);
    }

    /**
     * Gets filter description.
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
