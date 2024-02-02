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

use ApiPlatform\Doctrine\Common\Filter\DateFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\DateFilterTrait;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;

/**
 * The date filter allows to filter a collection by date intervals.
 *
 * Syntax: `?property[<after|before|strictly_after|strictly_before>]=value`.
 *
 * The value can take any date format supported by the [`\DateTime` constructor](https://www.php.net/manual/en/datetime.construct.php).
 *
 * The `after` and `before` filters will filter including the value whereas `strictly_after` and `strictly_before` will filter excluding the value.
 *
 * The date filter is able to deal with date properties having `null` values. Four behaviors are available at the property level of the filter:
 * - Use the default behavior of the DBMS: use `null` strategy
 * - Exclude items: use `ApiPlatform\Doctrine\Odm\Filter\DateFilter::EXCLUDE_NULL` (`exclude_null`) strategy
 * - Consider items as oldest: use `ApiPlatform\Doctrine\Odm\Filter\DateFilter::INCLUDE_NULL_BEFORE` (`include_null_before`) strategy
 * - Consider items as youngest: use `ApiPlatform\Doctrine\Odm\Filter\DateFilter::INCLUDE_NULL_AFTER` (`include_null_after`) strategy
 * - Always include items: use `ApiPlatform\Doctrine\Odm\Filter\DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER` (`include_null_before_and_after`) strategy
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(DateFilter::class, properties: ['createdAt'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.date_filter:
 *         parent: 'api_platform.doctrine.odm.date_filter'
 *         arguments: [ { createdAt: ~ } ]
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
 *                   filters: ['book.date_filter']
 * ```
 *
 * ```xml
 * <!-- api/config/services.xml -->
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <container
 *         xmlns="http://symfony.com/schema/dic/services"
 *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *         xsi:schemaLocation="http://symfony.com/schema/dic/services
 *         https://symfony.com/schema/dic/services/services-1.0.xsd">
 *     <services>
 *         <service id="book.date_filter" parent="api_platform.doctrine.odm.date_filter">
 *             <argument type="collection">
 *                 <argument key="createdAt"></argument>
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
 *                     <filter>book.date_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books by date with the following query: `/books?createdAt[after]=2018-03-19`.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DateFilter extends AbstractFilter implements DateFilterInterface
{
    use DateFilterTrait;

    public const DOCTRINE_DATE_TYPES = [
        MongoDbType::DATE => true,
        MongoDbType::DATE_IMMUTABLE => true,
    ];

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        // Expect $values to be an array having the period as keys and the date value as values
        if (
            !\is_array($values)
            || !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isDateField($property, $resourceClass)
        ) {
            return;
        }

        $matchField = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $nullManagement = $this->properties[$property] ?? null;

        if (self::EXCLUDE_NULL === $nullManagement) {
            $aggregationBuilder->match()->field($matchField)->notEqual(null);
        }

        if (isset($values[self::PARAMETER_BEFORE])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_BEFORE,
                $values[self::PARAMETER_BEFORE],
                $nullManagement
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_BEFORE])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_STRICTLY_BEFORE,
                $values[self::PARAMETER_STRICTLY_BEFORE],
                $nullManagement
            );
        }

        if (isset($values[self::PARAMETER_AFTER])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_AFTER,
                $values[self::PARAMETER_AFTER],
                $nullManagement
            );
        }

        if (isset($values[self::PARAMETER_STRICTLY_AFTER])) {
            $this->addMatch(
                $aggregationBuilder,
                $matchField,
                self::PARAMETER_STRICTLY_AFTER,
                $values[self::PARAMETER_STRICTLY_AFTER],
                $nullManagement
            );
        }
    }

    /**
     * Adds the match stage according to the chosen null management.
     */
    private function addMatch(Builder $aggregationBuilder, string $field, string $operator, $value, ?string $nullManagement = null): void
    {
        $value = $this->normalizeValue($value, $operator);

        if (null === $value) {
            return;
        }

        try {
            $value = new \DateTime($value);
        } catch (\Exception) {
            // Silently ignore this filter if it can not be transformed to a \DateTime
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('The field "%s" has a wrong date format. Use one accepted by the \DateTime constructor', $field)),
            ]);

            return;
        }

        $operatorValue = [
            self::PARAMETER_BEFORE => '$lte',
            self::PARAMETER_STRICTLY_BEFORE => '$lt',
            self::PARAMETER_AFTER => '$gte',
            self::PARAMETER_STRICTLY_AFTER => '$gt',
        ];

        if ((self::INCLUDE_NULL_BEFORE === $nullManagement && \in_array($operator, [self::PARAMETER_BEFORE, self::PARAMETER_STRICTLY_BEFORE], true))
            || (self::INCLUDE_NULL_AFTER === $nullManagement && \in_array($operator, [self::PARAMETER_AFTER, self::PARAMETER_STRICTLY_AFTER], true))
            || (self::INCLUDE_NULL_BEFORE_AND_AFTER === $nullManagement && \in_array($operator, [self::PARAMETER_AFTER, self::PARAMETER_STRICTLY_AFTER, self::PARAMETER_BEFORE, self::PARAMETER_STRICTLY_BEFORE], true))
        ) {
            $aggregationBuilder->match()->addOr(
                $aggregationBuilder->matchExpr()->field($field)->operator($operatorValue[$operator], $value),
                $aggregationBuilder->matchExpr()->field($field)->equals(null)
            );

            return;
        }

        $aggregationBuilder->match()->addAnd($aggregationBuilder->matchExpr()->field($field)->operator($operatorValue[$operator], $value));
    }
}
