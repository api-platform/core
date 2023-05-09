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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SearchFilter extends AbstractFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    public const DOCTRINE_INTEGER_TYPE = [MongoDbType::INTEGER, MongoDbType::INT];

    public function __construct(ManagerRegistry $managerRegistry, IriConverterInterface $iriConverter, ?IdentifiersExtractorInterface $identifiersExtractor, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

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
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, Operation $operation = null, array &$context = []): void
    {
        if (
            null === $value
            || !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $matchField = $field = $property;

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField, $field, $associations] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $caseSensitive = true;
        $strategy = $this->properties[$property] ?? self::STRATEGY_EXACT;

        // prefixing the strategy with i makes it case insensitive
        if (str_starts_with($strategy, 'i')) {
            $strategy = substr($strategy, 1);
            $caseSensitive = false;
        }

        /** @var MongoDBClassMetadata */
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field) && !$metadata->hasAssociation($field)) {
            if ('id' === $field) {
                $values = array_map($this->getIdFromValue(...), $values);
            }

            if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
                $this->logger->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $field)),
                ]);

                return;
            }

            $this->addEqualityMatchStrategy($strategy, $aggregationBuilder, $field, $matchField, $values, $caseSensitive, $metadata);

            return;
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $values = array_map($this->getIdFromValue(...), $values);

        $associationResourceClass = $metadata->getAssociationTargetClass($field);
        $associationFieldIdentifier = $metadata->getIdentifierFieldNames()[0];
        $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);

        if (!$this->hasValidValues($values, $doctrineTypeField)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $property)),
            ]);

            return;
        }

        $this->addEqualityMatchStrategy($strategy, $aggregationBuilder, $field, $matchField, $values, $caseSensitive, $metadata);
    }

    /**
     * Add equality match stage according to the strategy.
     */
    private function addEqualityMatchStrategy(string $strategy, Builder $aggregationBuilder, string $field, string $matchField, array $values, bool $caseSensitive, ClassMetadata $metadata): void
    {
        $inValues = [];
        foreach ($values as $inValue) {
            $inValues[] = $this->getEqualityMatchStrategyValue($strategy, $field, $inValue, $caseSensitive, $metadata);
        }

        $aggregationBuilder
            ->match()
            ->field($matchField)
            ->in($inValues);
    }

    /**
     * Get equality match value according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     */
    private function getEqualityMatchStrategyValue(string $strategy, string $field, mixed $value, bool $caseSensitive, ClassMetadata $metadata): mixed
    {
        $type = $metadata->getTypeOfField($field);

        if (!MongoDbType::hasType($type)) {
            return $value;
        }
        if (MongoDbType::STRING !== $type) {
            return MongoDbType::getType($type)->convertToDatabaseValue($value);
        }

        $quotedValue = preg_quote($value);

        return match ($strategy) {
            self::STRATEGY_EXACT => $caseSensitive ? $value : new Regex("^$quotedValue$", 'i'),
            self::STRATEGY_PARTIAL => new Regex($quotedValue, $caseSensitive ? '' : 'i'),
            self::STRATEGY_START => new Regex("^$quotedValue", $caseSensitive ? '' : 'i'),
            self::STRATEGY_END => new Regex("$quotedValue$", $caseSensitive ? '' : 'i'),
            self::STRATEGY_WORD_START => new Regex("(^$quotedValue.*|.*\s$quotedValue.*)", $caseSensitive ? '' : 'i'),
            default => throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy)),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(string $doctrineType): string
    {
        return match ($doctrineType) {
            MongoDbType::INT, MongoDbType::INTEGER => 'int',
            MongoDbType::BOOL, MongoDbType::BOOLEAN => 'bool',
            MongoDbType::DATE, MongoDbType::DATE_IMMUTABLE => \DateTimeInterface::class,
            MongoDbType::FLOAT => 'float',
            default => 'string',
        };
    }
}
