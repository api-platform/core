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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\NumericFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Filters the collection by numeric values.
 *
 * Filters collection by equality of numeric properties.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not numeric, the property is ignored.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class NumericFilter extends AbstractFilter
{
    use NumericFilterTrait;

    /**
     * Type of numeric in Doctrine.
     *
     * @see http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/types.html
     */
    public const DOCTRINE_NUMERIC_TYPES = [
        Types::BIGINT => true,
        Types::DECIMAL => true,
        Types::FLOAT => true,
        Types::INTEGER => true,
        Types::SMALLINT => true,
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isNumericField($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($value, $property);
        if (null === $values) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }

        $valueParameter = $queryNameGenerator->generateParameterName($field);

        if (1 === \count($values)) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values[0], (string) $this->getDoctrineFieldType($property, $resourceClass));
        } else {
            $queryBuilder
                ->andWhere(sprintf('%s.%s IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType = null): string
    {
        if (null === $doctrineType || Types::DECIMAL === $doctrineType) {
            return 'string';
        }

        if (Types::FLOAT === $doctrineType) {
            return 'float';
        }

        return 'int';
    }
}
