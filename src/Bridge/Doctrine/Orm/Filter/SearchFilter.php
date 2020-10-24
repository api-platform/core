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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filter the collection by given properties.
 *
 * Filter supports multiple strategies per property and also property aliasing. It can be configured as follows:
 * properties = {
 *   "<queryParamName>" : {
 *     "property": "<entity property name, defaults to queryParamName>,
 *     "defaultStrategy": "exact|partial|ipartial"
 *   }
 * }
 *
 * Filter can then be invoked as follows
 * ?queryParamName[<strategy>]=<value>
 * ?queryParamName[<strategy>][]=<value>
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SearchFilter extends AbstractContextAwareFilter implements SearchFilterInterface, ContextAwareFilterInterface
{
    use SearchFilterTrait;

    public const DOCTRINE_INTEGER_TYPE = DBALType::INTEGER;

    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, IdentifiersExtractorInterface $identifiersExtractor = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);

        if (null === $identifiersExtractor) {
            @trigger_error('Not injecting ItemIdentifiersExtractor is deprecated since API Platform 2.5 and can lead to unexpected behaviors, it will not be possible anymore in API Platform 3.0.', E_USER_DEPRECATED);
        }

        $this->iriConverter = $iriConverter;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    protected function getIriConverter(): IriConverterInterface
    {
        return $this->iriConverter;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
    }

    /**
     * @param $value
     *
     * @return array = [
     *               [
     *               'propertyAlias' => 'original requested property'
     *               'property' => 'actual resource property name'
     *               'strategy' => 'strategy',
     *               'value' => 'value',
     *               ]
     *               ]
     */
    protected function getFilteringOperations(string $propertyAlias, $value)
    {
        // Extract which entity property we are referring to, keep BC with old filter implementation
        $propertyData = $this->properties[$propertyAlias] ?? self::STRATEGY_EXACT;
        if (\is_array($propertyData)) {
            $property = $propertyData['property'] ?? $propertyAlias;
            $strategy = $propertyData['defaultStrategy'] ?? self::STRATEGY_EXACT;
        } else {
            // BC Layer
            // TODO Throw deprecation notice?
            $property = $propertyAlias;
            $strategy = $propertyData;
        }
        if (\is_array($value)) {
            $operations = [];
            // If value is an array, it could mean that:
            //   - the user wants to specify a strategy (e.g. property[exact]='test')
            //   - the user is specifying multiple values for the selected defaultStrategy (e.g. property[]='test'&property[]='test2')
            //   - other filters are active on the same property
            // Thus, here we need to extract all operations related to the SearchFilter
            $arrayValues = [];
            foreach ($value as $key => $val) {
                if (\is_int($key)) {
                    // User is appending multiple values to default strategy
                    $arrayValues[] = $val;
                }
                if (\is_string($key) && \array_key_exists($key, self::STRATEGIES_MAP)) {
                    // User is requesting a strategy and said strategy is valid
                    $operations[] = [
                        'propertyAlias' => $propertyAlias,
                        'property' => $property,
                        'strategy' => $key,
                        'value' => $val,
                    ];
                }
            }
            if ($arrayValues) {
                $operations[] = [
                    'propertyAlias' => $propertyAlias,
                    'property' => $property,
                    'strategy' => $strategy,
                    'value' => $arrayValues,
                ];
            }

            return $operations;
        }

        // User is not specifying a strategy, use the default one
        return [
            [
                'propertyAlias' => $propertyAlias,
                'property' => $property,
                'strategy' => $strategy,
                'value' => $value,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (null === $value) {
            return;
        }
        // Here, $property is actually the queryParamName, we need to transform it into an actual resource property
        $operations = $this->getFilteringOperations($property, $value);
        foreach ($operations as $op) {
            if (
                !$this->isPropertyEnabled($op['propertyAlias'], $resourceClass) ||
                !$this->isPropertyMapped($op['property'], $resourceClass, true)
            ) {
                continue;
            }
            $this->doFilterProperty($op['property'], $op['strategy'], $op['value'], $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);
        }
    }

    protected function doFilterProperty(string $property, string $strategy, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass);
        }

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $caseSensitive = true;
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

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
        if (self::STRATEGY_EXACT !== $strategy) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Field "%s" is an association and supports only the exact strategy', $field)),
            ]);

            return;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);
        $associationFieldIdentifier = 'id';
        $doctrineTypeField = $this->getDoctrineFieldType($property, $resourceClass);

        if (null !== $this->identifiersExtractor) {
            $associationResourceClass = $metadata->getAssociationTargetClass($field);
            $associationFieldIdentifier = $this->identifiersExtractor->getIdentifiersFromResourceClass($associationResourceClass)[0];
            $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);
        }

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
            ]);

            return;
        }

        $association = $field;
        $valueParameter = $queryNameGenerator->generateParameterName($association);
        if ($metadata->isCollectionValuedAssociation($association)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $association);
            $associationField = $associationFieldIdentifier;
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

    /**
     * Creates a function that will wrap a Doctrine expression according to the
     * specified case sensitivity.
     *
     * For example, "o.name" will get wrapped into "LOWER(o.name)" when $caseSensitive
     * is false.
     */
    protected function createWrapCase(bool $caseSensitive): \Closure
    {
        return static function (string $expr) use ($caseSensitive): string {
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
