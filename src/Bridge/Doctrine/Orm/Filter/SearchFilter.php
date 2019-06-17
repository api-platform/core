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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SearchFilter extends AbstractContextAwareFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    public const DOCTRINE_INTEGER_TYPE = DBALType::INTEGER;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ?RequestStack $requestStack,
        IriConverterInterface $iriConverter,
        IdentifierConverterInterface $identifierConverter,
        PropertyAccessorInterface $propertyAccessor = null,
        LoggerInterface $logger = null,
        array $properties = null
    ) {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties);

        $this->iriConverter = $iriConverter;
        $this->identifierConverter = $identifierConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    protected function getIriConverter(): IriConverterInterface
    {
        return $this->iriConverter;
    }

    protected function getIdentifierConverter(): IdentifierConverterInterface
    {
        return $this->identifierConverter;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (
            null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $caseSensitive = true;

        $doctrineType = $this->getDoctrineFieldType($property, $resourceClass);

        if ($metadata->hasField($field)) {
            if ('id' === $field) {
                $values = array_map([$this, 'getIdFromValue'], $values, array_fill(0, \count($values), $resourceClass));
            }

            if (!$this->hasValidValues($values, $doctrineType)) {
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
                $this->addWhereByStrategy(
                    $strategy,
                    $queryBuilder,
                    $queryNameGenerator,
                    $alias,
                    $field,
                    $values[0],
                    $caseSensitive,
                    $doctrineType
                );

                return;
            }

            if (self::STRATEGY_EXACT !== $strategy) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('"%s" strategy selected for "%s" property, but only "%s" strategy supports multiple values', $strategy, $property, self::STRATEGY_EXACT)),
                ]);

                return;
            }

            $this->setOrClause($queryBuilder, $queryNameGenerator, $values, $alias, $field, $doctrineType, $caseSensitive);
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

        if ($metadata->isCollectionValuedAssociation($association)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $association);
            $associationField = 'id';
        } else {
            $associationAlias = $alias;
            $associationField = $field;
        }

        if (1 === \count($values)) {
            $valueParameter = $queryNameGenerator->generateParameterName($association);
            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $associationAlias, $associationField, $valueParameter))
                ->setParameter($valueParameter, $values[0], (string) $doctrineType);
        } else {
            $this->setOrClause($queryBuilder, $queryNameGenerator, $values, $associationAlias, $associationField, $doctrineType, $caseSensitive);
        }
    }

    /**
     * Doctrine does not support custom types for `IN` queries, see https://github.com/doctrine/orm/issues/6934
     * This method substitutes the use of `IN` with `OR` and attaches the parameters to the query builder.
     */
    private function setOrClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, array $values, $alias, $field, $doctrineType, $caseSensitive)
    {
        $wrapCase = $this->createWrapCase($caseSensitive);

        $orX = $queryBuilder->expr()->orX();

        foreach ($values as $value) {
            $valueParameter = $queryNameGenerator->generateParameterName($field);
            $orX->add(sprintf($wrapCase('%s.%s').' = :%s', $alias, $field, $valueParameter));
            $queryBuilder->setParameter($valueParameter, $caseSensitive ? $value : strtolower($value), $doctrineType);
        }

        $queryBuilder->andWhere($orX);
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    protected function addWhereByStrategy(string $strategy, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, $value, bool $caseSensitive, $doctrineType)
    {
        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = $queryNameGenerator->generateParameterName($field);

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' = '.$wrapCase(':%s'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value, $doctrineType);
                break;
            case self::STRATEGY_PARTIAL:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' LIKE '.$wrapCase('CONCAT(\'%%\', :%s, \'%%\')'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value, $doctrineType);
                break;
            case self::STRATEGY_START:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' LIKE '.$wrapCase('CONCAT(:%s, \'%%\')'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value, $doctrineType);
                break;
            case self::STRATEGY_END:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%s.%s').' LIKE '.$wrapCase('CONCAT(\'%%\', :%s)'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value, $doctrineType);
                break;
            case self::STRATEGY_WORD_START:
                $queryBuilder
                    ->andWhere(sprintf($wrapCase('%1$s.%2$s').' LIKE '.$wrapCase('CONCAT(:%3$s, \'%%\')').' OR '.$wrapCase('%1$s.%2$s').' LIKE '.$wrapCase('CONCAT(\'%% \', :%3$s, \'%%\')'), $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value, $doctrineType);
                break;
            default:
                throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
        }
    }

    /**
     * Creates a function that will wrap a Doctrine expression according to the
     * specified case sensitivity.
     *
     * For example, "o.name" will get wrapped into "LOWER(o.name)" when $caseSensitive
     * is false.
     */
    protected function createWrapCase(bool $caseSensitive): \Closure
    {
        return function (string $expr) use ($caseSensitive): string {
            if ($caseSensitive) {
                return $expr;
            }

            return sprintf('LOWER(%s)', $expr);
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType): string
    {
        switch ($doctrineType) {
            case DBALType::TARRAY:
                return 'array';
            case DBALType::BIGINT:
            case DBALType::INTEGER:
            case DBALType::SMALLINT:
                return 'int';
            case DBALType::BOOLEAN:
                return 'bool';
            case DBALType::DATE:
            case DBALType::TIME:
            case DBALType::DATETIME:
            case DBALType::DATETIMETZ:
                return \DateTimeInterface::class;
            case DBALType::FLOAT:
                return 'float';
        }

        if (\defined(DBALType::class.'::DATE_IMMUTABLE')) {
            switch ($doctrineType) {
                case DBALType::DATE_IMMUTABLE:
                case DBALType::TIME_IMMUTABLE:
                case DBALType::DATETIME_IMMUTABLE:
                case DBALType::DATETIMETZ_IMMUTABLE:
                    return \DateTimeInterface::class;
            }
        }

        return 'string';
    }
}
