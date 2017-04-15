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
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;
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
class SearchFilter extends AbstractFilter
{
    /**
     * @var string Exact matching
     */
    const STRATEGY_EXACT = 'exact';

    /**
     * @var string The value must be contained in the field
     */
    const STRATEGY_PARTIAL = 'partial';

    /**
     * @var string Finds fields that are starting with the value
     */
    const STRATEGY_START = 'start';

    /**
     * @var string Finds fields that are ending with the value
     */
    const STRATEGY_END = 'end';

    /**
     * @var string Finds fields that are starting with the word
     */
    const STRATEGY_WORD_START = 'word_start';

    protected $iriConverter;
    protected $propertyAccessor;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

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

        foreach ($properties as $property => $strategy) {
            if (!$this->isPropertyMapped($property, $resourceClass, true)) {
                continue;
            }

            if ($this->isPropertyNested($property)) {
                $propertyParts = $this->splitPropertyParts($property);
                $field = $propertyParts['field'];
                $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            } else {
                $field = $property;
                $metadata = $this->getClassMetadata($resourceClass);
            }

            if ($metadata->hasField($field)) {
                $typeOfField = $this->getType($metadata->getTypeOfField($field));
                $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;
                $filterParameterNames = [$property];

                if (self::STRATEGY_EXACT === $strategy) {
                    $filterParameterNames[] = $property.'[]';
                }

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $property,
                        'type' => $typeOfField,
                        'required' => false,
                        'strategy' => $strategy,
                    ];
                }
            } elseif ($metadata->hasAssociation($field)) {
                $filterParameterNames = [
                    $property,
                    $property.'[]',
                ];

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $property,
                        'type' => 'string',
                        'required' => false,
                        'strategy' => self::STRATEGY_EXACT,
                    ];
                }
            }
        }

        return $description;
    }

    /**
     * Converts a Doctrine type in PHP type.
     *
     * @param string $doctrineType
     *
     * @return string
     */
    private function getType(string $doctrineType): string
    {
        switch ($doctrineType) {
            case Type::TARRAY:
                return 'array';
            case Type::BIGINT:
            case Type::INTEGER:
            case Type::SMALLINT:
                return 'int';
            case Type::BOOLEAN:
                return 'bool';
            case Type::DATE:
            case Type::TIME:
            case Type::DATETIME:
            case Type::DATETIMETZ:
                    return \DateTimeInterface::class;
            case Type::FLOAT:
                return 'float';
        }

        return 'string';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (
            null === $value ||
            !$this->isPropertyEnabled($property) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $alias = 'o';
        $field = $property;

        if ($this->isPropertyNested($property)) {
            list($alias, $field, $associations) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator);
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

            $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

            // prefixing the strategy with i makes it case insensitive
            if (strpos($strategy, 'i') === 0) {
                $strategy = substr($strategy, 1);
                $caseSensitive = false;
            }

            if (1 === count($values)) {
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

        $association = $field;
        $valueParameter = $queryNameGenerator->generateParameterName($association);

        $associationAlias = $this->addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $association);

        if (1 === count($values)) {
            $queryBuilder
                ->andWhere(sprintf('%s.id = :%s', $associationAlias, $valueParameter))
                ->setParameter($valueParameter, $values[0]);
        } else {
            $queryBuilder
                ->andWhere(sprintf('%s.id IN (:%s)', $associationAlias, $valueParameter))
                ->setParameter($valueParameter, $values);
        }
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @param string                      $strategy
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $alias
     * @param string                      $field
     * @param mixed                       $value
     * @param bool                        $caseSensitive
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
     *
     * @param bool $caseSensitive
     *
     * @return \Closure
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
     * Gets the ID from an IRI or a raw ID.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function getIdFromValue(string $value)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($value, ['fetch_data' => false])) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }

    /**
     * Normalize the values array.
     *
     * @param array $values
     *
     * @return array
     */
    protected function normalizeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (!is_int($key) || !is_string($value)) {
                unset($values[$key]);
            }
        }

        return array_values($values);
    }
}
