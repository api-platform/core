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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\OrderFilterTrait;
use ApiPlatform\Doctrine\Common\Filter\PropertyPlaceholderOpenApiParameterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * The order filter allows to sort a collection against the given properties.
 *
 * Syntax: `?order[property]=<asc|desc>`.
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(OrderFilter::class, properties: ['id', 'title'], arguments: ['orderParameterName' => 'order'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.order_filter:
 *         parent: 'api_platform.doctrine.orm.order_filter'
 *         arguments: [ $properties: { id: ~, title: ~ }, $orderParameterName: order ]
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
 *                   filters: ['book.order_filter']
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
 *         <service id="book.order_filter" parent="api_platform.doctrine.orm.order_filter">
 *             <argument type="collection" key="properties">
 *                 <argument key="id"/>
 *                 <argument key="title"/>
 *             </argument>
 *             <argument key="orderParameterName">order</argument>
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
 *                     <filter>book.order_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books by title in ascending order and then by ID in descending order with the following query: `/books?order[title]=desc&order[id]=asc`.
 *
 * By default, whenever the query does not specify the direction explicitly (e.g.: `/books?order[title]&order[id]`), filters will not be applied unless you configure a default order direction to use:
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(OrderFilter::class, properties: ['id' => 'ASC', 'title' => 'DESC'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.order_filter:
 *         parent: 'api_platform.doctrine.orm.order_filter'
 *         arguments: [ { id: ASC, title: DESC } ]
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
 *                   filters: ['book.order_filter']
 * ```
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
 *         <service id="book.order_filter" parent="api_platform.doctrine.orm.order_filter">
 *             <argument type="collection">
 *                 <argument key="id">ASC</argument>
 *                 <argument key="title">DESC</argument>
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
 *                     <filter>book.order_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * When the property used for ordering can contain `null` values, you may want to specify how `null` values are treated in the comparison:
 * - Use the default behavior of the DBMS: use `null` strategy
 * - Exclude items: use `ApiPlatform\Doctrine\Orm\Filter\OrderFilter::NULLS_SMALLEST` (`nulls_smallest`) strategy
 * - Consider items as oldest: use `ApiPlatform\Doctrine\Orm\Filter\OrderFilter::NULLS_LARGEST` (`nulls_largest`) strategy
 * - Consider items as youngest: use `ApiPlatform\Doctrine\Orm\Filter\OrderFilter::NULLS_ALWAYS_FIRST` (`nulls_always_first`) strategy
 * - Always include items: use `ApiPlatform\Doctrine\Orm\Filter\OrderFilter::NULLS_ALWAYS_LAST` (`nulls_always_last`) strategy
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class OrderFilter extends AbstractFilter implements OrderFilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use OrderFilterTrait;
    use PropertyPlaceholderOpenApiParameterTrait;

    public function __construct(?ManagerRegistry $managerRegistry = null, string $orderParameterName = 'order', ?LoggerInterface $logger = null, ?array $properties = null, ?NameConverterInterface $nameConverter = null, private readonly ?string $orderNullsComparison = null)
    {
        if (null !== $properties) {
            $properties = array_map(static function ($propertyOptions) {
                // shorthand for default direction
                if (\is_string($propertyOptions)) {
                    $propertyOptions = [
                        'default_direction' => $propertyOptions,
                    ];
                }

                return $propertyOptions;
            }, $properties);
        }

        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->orderParameterName = $orderParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            isset($context['filters'])
            && (!isset($context['filters'][$this->orderParameterName]) || !\is_array($context['filters'][$this->orderParameterName]))
            && !isset($context['parameter'])
        ) {
            return;
        }

        $parameter = $context['parameter'] ?? null;
        if (null !== ($value = $context['filters'][$parameter?->getProperty()] ?? null)) {
            $this->filterProperty($this->denormalizePropertyName($parameter->getProperty()), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

            return;
        }

        foreach ($context['filters'][$this->orderParameterName] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!$this->isPropertyEnabled($property, $resourceClass) || !$this->isPropertyMapped($property, $resourceClass)) {
            return;
        }

        $direction = $this->normalizeValue($value, $property);
        if (null === $direction) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN);
        }

        if (null !== $nullsComparison = $this->properties[$property]['nulls_comparison'] ?? $this->orderNullsComparison) {
            $nullsDirection = self::NULLS_DIRECTION_MAP[$nullsComparison][$direction];

            $nullRankHiddenField = \sprintf('_%s_%s_null_rank', $alias, str_replace('.', '_', $field));

            $queryBuilder->addSelect(\sprintf('CASE WHEN %s.%s IS NULL THEN 0 ELSE 1 END AS HIDDEN %s', $alias, $field, $nullRankHiddenField));
            $queryBuilder->addOrderBy($nullRankHiddenField, $nullsDirection);
        }

        $queryBuilder->addOrderBy(\sprintf('%s.%s', $alias, $field), $direction);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['asc', 'desc']];
    }
}
