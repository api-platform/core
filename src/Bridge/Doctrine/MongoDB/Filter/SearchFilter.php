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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\AbstractSearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface as CommonQueryNameGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SearchFilter extends AbstractSearchFilter
{
    /**
     * {@inheritdoc}
     *
     * @param Builder                     $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     */
    protected function filterProperty(string $property, $value, $queryBuilder, CommonQueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (
            null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        return;

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            list($alias, $field, $associations) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
            $metadata = $this->getNestedMetadata($resourceClass, $associations);
        } else {
            $metadata = $this->getClassMetadata($resourceClass);
        }

        $values = $this->normalizeValues((array) $value);

        if (empty($values)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('At least one value is required, multiple values should be in "%1$s[]=firstvalue&%1$s[]=secondvalue" format', $property)),
            ]);

            return;
        }

        $caseSensitive = true;

        if ($metadata->hasField($field)) {
            if ('id' === $field) {
                $values = array_map([$this, 'getIdFromValue'], $values);
            }

            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
                ]);

                return;
            }

            $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

            // prefixing the strategy with i makes it case insensitive
            if (0 === strpos($strategy, 'i')) {
                $strategy = substr($strategy, 1);
                $caseSensitive = false;
            }

            if (1 === \count($values)) {
                $this->addWhereByStrategy($strategy, $queryBuilder, $queryNameGenerator, $alias, $field, $values[0], $caseSensitive);

                return;
            }

            if (self::STRATEGY_EXACT !== $strategy) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('"%s" strategy selected for "%s" property, but only "%s" strategy supports multiple values', $strategy, $property, self::STRATEGY_EXACT)),
                ]);

                return;
            }

            $wrapCase = $this->createWrapCase($caseSensitive);
            $valueParameter = $queryNameGenerator->generateParameterName($field);

            $queryBuilder
                ->andWhere(sprintf($wrapCase('%s.%s').' IN (:%s)', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $caseSensitive ? $values : array_map('strtolower', $values));
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);

        if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
            ]);

            return;
        }

        $association = $field;
        $valueParameter = $queryNameGenerator->generateParameterName($association);

        if ($metadata->isCollectionValuedAssociation($association)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $association);
            $associationField = 'id';
        } else {
            $associationAlias = $alias;
            $associationField = $field;
        }

        if (1 === \count($values)) {
            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values[0]);
        } else {
            $queryBuilder
                ->andWhere(sprintf('%s.%s IN (:%s)', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values);
        }
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    protected function addWhereByStrategy(string $strategy, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, $value, bool $caseSensitive)
    {
        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = $queryNameGenerator->generateParameterName($field);

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' = '.$wrapCase(':%s'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
                break;
            case self::STRATEGY_PARTIAL:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' LIKE '.$wrapCase('CONCAT(\'%%\', :%s, \'%%\')'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
                break;
            case self::STRATEGY_START:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' LIKE '.$wrapCase('CONCAT(:%s, \'%%\')'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
                break;
            case self::STRATEGY_END:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' LIKE '.$wrapCase('CONCAT(\'%%\', :%s)'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
                break;
            case self::STRATEGY_WORD_START:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%1$s.%2$s').' LIKE '.$wrapCase('CONCAT(:%3$s, \'%%\')').' OR '.$wrapCase('%1$s.%2$s').' LIKE '.$wrapCase('CONCAT(\'%% \', :%3$s, \'%%\')'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
                break;
            default:
                throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
        }
    }
}
