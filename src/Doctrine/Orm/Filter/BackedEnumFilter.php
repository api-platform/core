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

use ApiPlatform\Doctrine\Common\Filter\BackedEnumFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * The backed enum filter allows you to search on backed enum fields and values.
 *
 * Note: it is possible to filter on properties and relations too.
 *
 * Syntax: `?property=foo`.
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(BackedEnumFilter::class, properties: ['status'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.backed_enum_filter:
 *         parent: 'api_platform.doctrine.orm.backed_enum_filter'
 *         arguments: [ { status: ~ } ]
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
 *                   filters: ['book.backed_enum_filter']
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
 *         <service id="book.backed_enum_filter" parent="api_platform.doctrine.orm.backed_enum_filter">
 *             <argument type="collection">
 *                 <argument key="status"/>
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
 *                     <filter>book.backed_enum_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books with the following query: `/books?status=published`.
 *
 * @author Rémi Marseille <marseille.remi@gmail.com>
 */
final class BackedEnumFilter extends AbstractFilter
{
    use BackedEnumFilterTrait;

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
            || !$this->isBackedEnumField($property, $resourceClass)
        ) {
            return;
        }

        $values = \is_array($value) ? $value : [$value];

        $normalizedValues = array_filter(array_map(
            fn ($v) => $this->normalizeValue($v, $property),
            $values
        ));

        if (empty($normalizedValues)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }

        $valueParameter = $queryNameGenerator->generateParameterName($field);

        if (1 === \count($values)) {
            $queryBuilder
                ->andWhere(\sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $values[0]);

            return;
        }

        $queryBuilder
            ->andWhere(\sprintf('%s.%s IN (:%s)', $alias, $field, $valueParameter))
            ->setParameter($valueParameter, $values);
    }

    /**
     * {@inheritdoc}
     */
    protected function isBackedEnumField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        if (!$metadata instanceof ClassMetadata) {
            return false;
        }

        $fieldMapping = $metadata->fieldMappings[$propertyParts['field']];

        // Doctrine ORM 2.x returns an array and Doctrine ORM 3.x returns a FieldMapping object
        if ($fieldMapping instanceof FieldMapping) {
            $fieldMapping = (array) $fieldMapping;
        }

        if (!($enumType = $fieldMapping['enumType'] ?? null)) {
            return false;
        }

        if (!($enumType::cases()[0] ?? null) instanceof \BackedEnum) {
            return false;
        }

        $this->enumTypes[$property] = $enumType;

        return true;
    }
}
