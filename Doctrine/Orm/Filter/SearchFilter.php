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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Util\QueryNameGenerator;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
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

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @param ManagerRegistry           $managerRegistry
     * @param IriConverterInterface     $iriConverter
     * @param PropertyAccessorInterface $propertyAccessor
     * @param null|array                $properties       Null to allow filtering on all properties with the exact strategy or a map of property name with strategy.
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        IriConverterInterface $iriConverter,
        PropertyAccessorInterface $propertyAccessor,
        array $properties = null
    ) {
        parent::__construct($managerRegistry, $properties);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        foreach ($this->extractProperties($request) as $property => $value) {
            if (
                !$this->isPropertyEnabled($property) ||
                !$this->isPropertyMapped($property, $resource, true) ||
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

                $metadata = $this->getNestedMetadata($resource, $propertyParts['associations']);
            } else {
                $metadata = $this->getClassMetadata($resource);
            }

            $propertyIdentifier = $this->getIdentifierFieldName($metadata);

            if ($metadata->hasField($field)) {
                if (!is_string($value)) {
                    continue;
                }

                if ($propertyIdentifier === $field) {
                    $value = $this->getFilterValueFromUrl($propertyIdentifier, $value);
                }

                $strategy = null !== $this->properties ? $this->properties[$property] : self::STRATEGY_EXACT;

                $this->addWhereByStrategy($strategy, $queryBuilder, $alias, $field, $value);

                continue;
            }

            if ($metadata->isSingleValuedAssociation($field)) {
                if (!is_string($value)) {
                    continue;
                }

                $value = $this->getFilterValueFromUrl($propertyIdentifier, $value);

                $association = $field;
                $associationAlias = QueryNameGenerator::generateJoinAlias($association);
                $valueParameter = QueryNameGenerator::generateParameterName($association);

                $queryBuilder
                    ->join(sprintf('%s.%s', $alias, $association), $associationAlias)
                    ->andWhere(sprintf('%s.%s = :%s', $associationAlias, $propertyIdentifier, $valueParameter))
                    ->setParameter($valueParameter, $value);

                continue;
            }

            if ($metadata->isCollectionValuedAssociation($field)) {
                $values = $value;
                if (!is_array($values)) {
                    $values = [$value];
                }

                $filteredValues = [];
                foreach ($values as $k => $v) {
                    if (is_int($k) && is_string($v)) {
                        $filteredValues[$k] = [$propertyIdentifier, $v];
                    }
                }

                if (empty($filteredValues)) {
                    continue;
                }

                $filteredValues = array_map(
                    function ($args) {
                        return call_user_func_array([$this, 'getFilterValueFromUrl'], $args);
                    },
                    $filteredValues
                );

                $association = $field;
                $associationAlias = QueryNameGenerator::generateJoinAlias($association);
                $valuesParameter = QueryNameGenerator::generateParameterName($association);

                $queryBuilder
                    ->join(sprintf('%s.%s', $alias, $association), $associationAlias)
                    ->andWhere(sprintf('%s.%s IN (:%s)', $associationAlias, $propertyIdentifier, $valuesParameter))
                    ->setParameter($valuesParameter, $filteredValues)
                ;
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
     * @return string
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    private function addWhereByStrategy($strategy, QueryBuilder $queryBuilder, $alias, $field, $value)
    {
        $valueParameter = QueryNameGenerator::generateParameterName($field);

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, $value);

            case self::STRATEGY_PARTIAL:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s LIKE :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, sprintf('%%%s%%', $value));

            case self::STRATEGY_START:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s LIKE :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, sprintf('%s%%', $value));

            case self::STRATEGY_END:
                return $queryBuilder
                    ->andWhere(sprintf('%s.%s LIKE :%s', $alias, $field, $valueParameter))
                    ->setParameter($valueParameter, sprintf('%%%s', $value));

            case self::STRATEGY_WORD_START:
                return $queryBuilder
                    ->andWhere(sprintf('%1$s.%2$s LIKE :%3$s_1 OR %1$s.%2$s LIKE :%3$s_2', $alias, $field, $valueParameter))
                    ->setParameter(sprintf('%s_1', $valueParameter), sprintf('%s%%', $value))
                    ->setParameter(sprintf('%s_2', $valueParameter), sprintf('%% %s%%', $value));
        }

        throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
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

        foreach ($properties as $property => $strategy) {
            if (!$this->isPropertyMapped($property, $resource, true)) {
                continue;
            }

            if ($this->isPropertyNested($property)) {
                $propertyParts = $this->splitPropertyParts($property);

                $field = $propertyParts['field'];

                $metadata = $this->getNestedMetadata($resource, $propertyParts['associations']);
            } else {
                $field = $property;

                $metadata = $this->getClassMetadata($resource);
            }

            if ($metadata->hasField($field)) {
                $description[$property] = [
                    'property' => $property,
                    'type' => $metadata->getTypeOfField($field),
                    'required' => false,
                    'strategy' => isset($this->properties[$property]) ? $this->properties[$property] : self::STRATEGY_EXACT,
                ];
            } elseif ($metadata->hasAssociation($field)) {
                $association = $field;
                $paramName = $property;
                if ($metadata->isCollectionValuedAssociation($association)) {
                    $paramName .= '[]';
                }
                $description[$paramName] = [
                    'property' => $property,
                    'type' => 'iri',
                    'required' => false,
                    'strategy' => self::STRATEGY_EXACT,
                ];
            }
        }

        return $description;
    }

    /**
     * Gets the ID from an URI or a raw ID.
     *
     * @param string $identifier
     * @param string $value
     *
     * @return string
     */
    private function getFilterValueFromUrl($identifier, $value)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($value)) {
                return $this->propertyAccessor->getValue($item, $identifier);
            }
        } catch (\InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }

    /**
     * Gets the name of the identifier property.
     *
     * @param ClassMetadata $metadata
     *
     * @return string
     *
     * @throws RuntimeException
     */
    private function getIdentifierFieldName(ClassMetadata $metadata)
    {
        $identifier = $metadata->getIdentifierFieldNames();

        if (1 === count($identifier)) {
            return end($identifier);
        }

        throw new RuntimeException('Complex identifiers are not supported.');
    }
}
