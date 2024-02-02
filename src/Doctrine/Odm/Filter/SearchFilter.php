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

use ApiPlatform\Api\IdentifiersExtractorInterface as LegacyIdentifiersExtractorInterface;
use ApiPlatform\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
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
 * The search filter allows to filter a collection by given properties.
 *
 * The search filter supports `exact`, `partial`, `start`, `end`, and `word_start` matching strategies:
 * - `exact` strategy searches for fields that exactly match the value
 * - `partial` strategy uses `LIKE %value%` to search for fields that contain the value
 * - `start` strategy uses `LIKE value%` to search for fields that start with the value
 * - `end` strategy uses `LIKE %value` to search for fields that end with the value
 * - `word_start` strategy uses `LIKE value% OR LIKE % value%` to search for fields that contain words starting with the value
 *
 * Note: it is possible to filter on properties and relations too.
 *
 * Prepend the letter `i` to the filter if you want it to be case-insensitive. For example `ipartial` or `iexact`.
 * Note that this will use the `LOWER` function and *will* impact performance if there is no proper index.
 *
 * Case insensitivity may already be enforced at the database level depending on the [collation](https://en.wikipedia.org/wiki/Collation) used.
 * If you are using MySQL, note that the commonly used `utf8_unicode_ci` collation (and its sibling `utf8mb4_unicode_ci`)
 * are already case-insensitive, as indicated by the `_ci` part in their names.
 *
 * Note: Search filters with the `exact` strategy can have multiple values for the same property (in this case the
 * condition will be similar to a SQL IN clause).
 *
 * Syntax: `?property[]=foo&property[]=bar`.
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(SearchFilter::class, properties: ['isbn' => 'exact', 'description' => 'partial'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.search_filter:
 *         parent: 'api_platform.doctrine.odm.search_filter'
 *         arguments: [ { isbn: 'exact', description: 'partial' } ]
 *         tags:  [ 'api_platform.filter' ]
 *         # The following are mandatory only if a _defaults section is defined with inverted values.
 *         # You may want to isolate filters in a dedicated file to avoid adding the following lines (by adding them in the defaults section)
 *         autowire: false
 *         autoconfigure: false
 *         public: false
 *
 * # api/config/api_platform/resources.yaml
 * resources:
 *     App\Entity\Book:
 *         - operations:
 *               ApiPlatform\Metadata\GetCollection:
 *                   filters: ['book.search_filter']
 * ```
 *
 * ```xml
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <!-- api/config/services.xml -->
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <container
 *         xmlns="http://symfony.com/schema/dic/services"
 *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *         xsi:schemaLocation="http://symfony.com/schema/dic/services
 *         https://symfony.com/schema/dic/services/services-1.0.xsd">
 *     <services>
 *         <service id="book.search_filter" parent="api_platform.doctrine.odm.search_filter">
 *             <argument type="collection">
 *                 <argument key="isbn">exact</argument>
 *                 <argument key="description">partial</argument>
 *             </argument>
 *             <tag name="api_platform.filter"/>
 *         </service>
 *     </services>
 * </container>
 * <!-- api/config/api_platform/resources.xml -->
 * <resources
 *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
 *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
 *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
 *     <resource class="App\Entity\Book">
 *         <operations>
 *             <operation class="ApiPlatform\Metadata\GetCollection">
 *                 <filters>
 *                     <filter>book.search_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SearchFilter extends AbstractFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    public const DOCTRINE_INTEGER_TYPE = [MongoDbType::INTEGER, MongoDbType::INT];

    public function __construct(ManagerRegistry $managerRegistry, IriConverterInterface|LegacyIriConverterInterface $iriConverter, IdentifiersExtractorInterface|LegacyIdentifiersExtractorInterface|null $identifiersExtractor, ?PropertyAccessorInterface $propertyAccessor = null, ?LoggerInterface $logger = null, ?array $properties = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->iriConverter = $iriConverter;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    protected function getIriConverter(): LegacyIriConverterInterface|IriConverterInterface
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
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
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
