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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SearchFilter extends AbstractContextAwareFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    const DOCTRINE_INTEGER_TYPE = MongoDbType::INTEGER;

    public function __construct(ManagerRegistry $managerRegistry, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $logger, $properties);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (
            null === $value ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $matchField = $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField, $field, $associations] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $caseSensitive = true;

        if ($metadata->hasField($field) && !$metadata->hasAssociation($field)) {
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
                $aggregationBuilder
                    ->match()
                    ->field($matchField)
                    ->equals($this->addEqualityMatchStrategy($strategy, $values[0], $caseSensitive));

                return;
            }

            $inValues = [];
            foreach ($values as $inValue) {
                $inValues[] = $this->addEqualityMatchStrategy($strategy, $inValue, $caseSensitive);
            }
            $aggregationBuilder
                ->match()
                ->field($matchField)
                ->in($inValues);
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            return;
        }

        $values = array_map([$this, 'getIdFromValue'], $values);

        if (!$this->hasValidValues($values, $this->getDoctrineFieldType($property, $resourceClass))) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Values for field "%s" are not valid according to the doctrine type.', $property)),
            ]);

            return;
        }

        if (1 === \count($values)) {
            $aggregationBuilder
                ->match()
                ->field("$matchField.\$id")
                ->equals($values[0]);
        } else {
            $aggregationBuilder
                ->match()
                ->field("$matchField.\$id")
                ->in($values);
        }
    }

    /**
     * Add equality match stage according to the strategy.
     *
     * @throws InvalidArgumentException If strategy does not exist
     *
     * @return Regex|string
     */
    private function addEqualityMatchStrategy(string $strategy, $value, bool $caseSensitive)
    {
        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                return $caseSensitive ? $value : new Regex("^$value$", $caseSensitive ? '' : 'i');
            case self::STRATEGY_PARTIAL:
                return new Regex($value, $caseSensitive ? '' : 'i');
            case self::STRATEGY_START:
                return new Regex("^$value", $caseSensitive ? '' : 'i');
            case self::STRATEGY_END:
                return new Regex("$value$", $caseSensitive ? '' : 'i');
            case self::STRATEGY_WORD_START:
                return new Regex("(^$value.*|.*\s$value.*)", $caseSensitive ? '' : 'i');
            default:
                throw new InvalidArgumentException(sprintf('strategy %s does not exist.', $strategy));
        }
    }

    /**
     * Converts a Doctrine type in PHP type.
     */
    private function getType(string $doctrineType): string
    {
        switch ($doctrineType) {
            case MongoDbType::INT:
            case MongoDbType::INTEGER:
                return 'int';
            case MongoDbType::BOOL:
            case MongoDbType::BOOLEAN:
                return 'bool';
            case MongoDbType::DATE:
                return \DateTimeInterface::class;
            case MongoDbType::FLOAT:
                return 'float';
        }

        return 'string';
    }
}
