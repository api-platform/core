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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * Trait for filtering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait SearchFilterTrait
{
    use PropertyHelperTrait;

    protected IriConverterInterface $iriConverter;
    protected PropertyAccessorInterface $propertyAccessor;
    protected ?IdentifiersExtractorInterface $identifiersExtractor = null;

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
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

            $propertyName = $this->normalizePropertyName($property);
            if ($metadata->hasField($field)) {
                $typeOfField = $this->getType($metadata->getTypeOfField($field));
                $strategy = $this->getProperties()[$property] ?? self::STRATEGY_EXACT;
                $filterParameterNames = [$propertyName];

                if (\in_array($strategy, [self::STRATEGY_EXACT, self::STRATEGY_IEXACT], true)) {
                    $filterParameterNames[] = $propertyName.'[]';
                }

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $propertyName,
                        'type' => $typeOfField,
                        'required' => false,
                        'strategy' => $strategy,
                        'is_collection' => str_ends_with((string) $filterParameterName, '[]'),
                    ];
                }
            } elseif ($metadata->hasAssociation($field)) {
                $filterParameterNames = [
                    $propertyName,
                    $propertyName.'[]',
                ];

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $propertyName,
                        'type' => 'string',
                        'required' => false,
                        'strategy' => self::STRATEGY_EXACT,
                        'is_collection' => str_ends_with((string) $filterParameterName, '[]'),
                    ];
                }
            }
        }

        return $description;
    }

    /**
     * Converts a Doctrine type in PHP type.
     */
    abstract protected function getType(string $doctrineType): string;

    abstract protected function getProperties(): ?array;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getIriConverter(): IriConverterInterface;

    abstract protected function getPropertyAccessor(): PropertyAccessorInterface;

    abstract protected function normalizePropertyName(string $property): string;

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdFromValue(string $value): mixed
    {
        try {
            $iriConverter = $this->getIriConverter();
            $item = $iriConverter->getResourceFromIri($value, ['fetch_data' => false]);

            if (null === $this->identifiersExtractor) {
                $id = $this->getPropertyAccessor()->getValue($item, 'id');

                return $this->toUuidValue($id);
            }

            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($item);

            if (1 === \count($identifiers)) {
                $id = array_pop($identifiers);

                return $this->toUuidValue($id);
            }

            return array_map([$this, 'toUuidValue'], $identifiers);
        } catch (InvalidArgumentException) {
            // Do nothing, return the raw value
        }

        return $value;
    }

    private function toUuidValue(mixed $id): mixed
    {
        return ($id instanceof AbstractUid) ? $id->toBinary() : $id;
    }

    /**
     * Normalize the values array.
     */
    protected function normalizeValues(array $values, string $property): ?array
    {
        foreach ($values as $key => $value) {
            if (!\is_int($key) || !(\is_string($value) || \is_int($value))) {
                unset($values[$key]);
            }
        }

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(\sprintf('At least one value is required, multiple values should be in "%1$s[]=firstvalue&%1$s[]=secondvalue" format', $property)),
            ]);

            return null;
        }

        return array_values($values);
    }

    /**
     * When the field should be an integer, check that the given value is a valid one.
     */
    protected function hasValidValues(array $values, ?string $type = null): bool
    {
        foreach ($values as $value) {
            if (null !== $value && \in_array($type, (array) self::DOCTRINE_INTEGER_TYPE, true) && false === filter_var($value, \FILTER_VALIDATE_INT)) {
                return false;
            }
        }

        return true;
    }
}
