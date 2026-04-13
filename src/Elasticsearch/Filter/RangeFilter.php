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

namespace ApiPlatform\Elasticsearch\Filter;

/**
 * The range filter allows to find resources that [range](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html) the specified text on full text fields.
 *
 * Syntax: `?property[]=value`.
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Elasticsearch\Filter\RangeFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(RangeFilter::class, properties: ['title'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.range_filter:
 *         parent: 'api_platform.elasticsearch.range_filter'
 *         arguments: [ { title: ~ } ]
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
 *                   filters: ['book.range_filter']
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
 *         <service id="book.range_filter" parent="api_platform.elasticsearch.range_filter">
 *             <argument type="collection">
 *                 <argument key="title"/>
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
 *                     <filter>book.range_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books by title content with the following query: `/books?title=Foundation`.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html
 *
 * @author Saifallah Azzabi <seifallah.azzabi@gmail.com>
 */
final class RangeFilter extends AbstractSearchFilter
{
    public const GT = 'gt';

    public const GTE = 'gte';

    public const LT = 'lt';

    public const LTE = 'lte';
    /**
     * {@inheritdoc}
     */
    protected function getQuery(string $property, array $values, ?string $nestedPath): array
    {
        $rangeQuery = ['range' => [$property => []]];

        foreach ($values as $operator => $value) {
            if (\in_array($operator, [self::GT, self::GTE, self::LT, self::LTE], true)) {
                $rangeQuery['range'][$property][$operator] = $value;
            }
        }

        if (null !== $nestedPath) {
            return ['nested' => ['path' => $nestedPath, 'query' => $rangeQuery]];
        }

        return $rangeQuery;
    }
}
