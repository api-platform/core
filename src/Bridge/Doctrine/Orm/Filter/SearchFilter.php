<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
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
     * @var string Exact matching.
     */
    const STRATEGY_EXACT = 'exact';

    /**
     * @var string The value must be contained in the field.
     */
    const STRATEGY_PARTIAL = 'partial';

    /**
     * @var string Finds fields that are starting with the value.
     */
    const STRATEGY_START = 'start';

    /**
     * @var string Finds fields that are ending with the value.
     */
    const STRATEGY_END = 'end';

    /**
     * @var string Finds fields that are starting with the word.
     */
    const STRATEGY_WORD_START = 'word_start';

    private $requestStack;
    private $iriConverter;
    private $propertyAccessor;

    /**
     * @param ManagerRegistry           $managerRegistry
     * @param RequestStack              $requestStack
     * @param IriConverterInterface     $iriConverter
     * @param PropertyAccessorInterface $propertyAccessor
     * @param array|null                $properties       Null to allow filtering on all properties with the exact strategy or a map of property name with strategy.
     */
    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $properties);

        $this->requestStack = $requestStack;
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
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

        foreach ($this->extractProperties($request) as $property => $value) {
            if (
                !$this->isPropertyEnabled($property) ||
                !$this->isPropertyMapped($property, $resourceClass, true) ||
                null === $value
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

                $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            } else {
                $metadata = $this->getClassMetadata($resourceClass);
            }

            if ($metadata->hasField($field)) {
                $values = (array) $value;
                foreach ($values as $k => $v) {
                    if (!is_int($k) || !is_string($v)) {
                        unset($values[$k]);
                    }
                }
                $values = array_values($values);

                if (empty($values)) {
                    continue;
                }

                if ('id' === $field) {
                    $values = array_map([$this, 'getFilterValueFromUrl'], $values);
                }

                $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

                if (1 === count($values)) {
                    $this->addWhereByStrategy($strategy, $queryBuilder, $alias, $field, $values[0]);
                } else {
                    if (self::STRATEGY_EXACT !== $strategy) {
                        continue;
                    }

                    $valueParameter = QueryNameGenerator::generateParameterName($field);

                    $queryBuilder
                        ->andWhere(sprintf('%s.%s IN (:%s)', $alias, $field, $valueParameter))
                        ->setParameter($valueParameter, $values);
                }
            } elseif ($metadata->hasAssociation($field)) {
                $values = (array) $value;
                foreach ($values as $k => $v) {
                    if (!is_int($k) || !is_string($v)) {
                        unset($values[$k]);
                    }
                }
                $values = array_values($values);

                if (empty($values)) {
                    continue;
                }

                $values = array_map([$this, 'getFilterValueFromUrl'], $values);

                $association = $field;
                $associationAlias = QueryNameGenerator::generateJoinAlias($association);
                $valueParameter = QueryNameGenerator::generateParameterName($association);

                $queryBuilder
                    ->join(sprintf('%s.%s', $alias, $association), $associationAlias);

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
        }
    }

    /**
     * Adds where clause according to the strategy.
     *
     * @param string       $strategy
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $field
     * @param string       $value
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    private function addWhereByStrategy(string $strategy, QueryBuilder $queryBuilder, string $alias, string $field, string $value)
    {
        $valueParameter = QueryNameGenerator::generateParameterName($field);

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);
                break;

            case self::STRATEGY_PARTIAL:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s LIKE :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, sprintf('%%%s%%', $value));
                break;

            case self::STRATEGY_START:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s LIKE :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, sprintf('%s%%', $value));
                break;

            case self::STRATEGY_END:
                $queryBuilder
                    ->andWhere(sprintf('%s.%s LIKE :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, sprintf('%%%s', $value));
                break;

            case self::STRATEGY_WORD_START:
                $queryBuilder
                    ->andWhere(sprintf('%1$s.%2$s LIKE :%3$s_1 OR %1$s.%2$s LIKE :%3$s_2', $alias, $field, $valueParameter))
                    ->setParameter(sprintf('%s_1', $valueParameter), sprintf('%s%%', $value))
                    ->setParameter(sprintf('%s_2', $valueParameter), sprintf('%% %s%%', $value));
                break;

            default:
                throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
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
                $typeOfField = $metadata->getTypeOfField($field);
                $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;
                $filterParameterNames = [
                    $property,
                ];
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
                $association = $field;
                $filterParameterNames = [
                    $property,
                    $property.'[]',
                ];
                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $property,
                        'type' => 'iri',
                        'required' => false,
                        'strategy' => self::STRATEGY_EXACT,
                    ];
                }
            }
        }

        return $description;
    }

    /**
     * Gets the ID from an URI or a raw ID.
     *
     * @param string $value
     *
     * @return string
     */
    private function getFilterValueFromUrl(string $value) : string
    {
        if (null === $this->iriConverter) {
            return $value;
        }

        try {
            if ($item = $this->iriConverter->getItemFromIri($value)) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (\InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
