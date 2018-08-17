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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Abstract class for filtering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractSearchFilter extends AbstractContextAwareFilter
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

    /**
     * @param RequestStack|null $requestStack No prefix to prevent autowiring of this deprecated property
     */
    public function __construct(ManagerRegistry $managerRegistry, $requestStack = null, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null)
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

            if ($this->isPropertyNested($property, $resourceClass)) {
                $propertyParts = $this->splitPropertyParts($property, $resourceClass);
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

        if (\defined(Type::class.'::DATE_IMMUTABLE')) {
            switch ($doctrineType) {
                case Type::DATE_IMMUTABLE:
                case Type::TIME_IMMUTABLE:
                case Type::DATETIME_IMMUTABLE:
                case Type::DATETIMETZ_IMMUTABLE:
                    return \DateTimeInterface::class;
            }
        }

        return 'string';
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
     * Gets the ID from an IRI or a raw ID.
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
     */
    protected function normalizeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (!\is_int($key) || !\is_string($value)) {
                unset($values[$key]);
            }
        }

        return array_values($values);
    }

    /**
     * When the field should be an integer, check that the given value is a valid one.
     *
     * @param Type|string $type
     */
    protected function hasValidValues(array $values, $type = null): bool
    {
        foreach ($values as $key => $value) {
            if (Type::INTEGER === $type && null !== $value && false === filter_var($value, FILTER_VALIDATE_INT)) {
                return false;
            }
        }

        return true;
    }
}
