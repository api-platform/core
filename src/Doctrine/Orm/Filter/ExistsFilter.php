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

use ApiPlatform\Doctrine\Common\Filter\ExistsFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\ExistsFilterTrait;
use ApiPlatform\Doctrine\Common\Filter\PropertyPlaceholderOpenApiParameterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\ToOneOwningSideMapping;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * The exists filter allows you to select items based on a nullable field value. It will also check the emptiness of a collection association.
 *
 * Syntax: `?exists[property]=<true|false|1|0>`.
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiFilter;
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Doctrine\Orm\Filter\ExistFilter;
 *
 * #[ApiResource]
 * #[ApiFilter(ExistFilter::class, properties: ['comment'])]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.exist_filter:
 *         parent: 'api_platform.doctrine.orm.exist_filter'
 *         arguments: [ { comment: ~ } ]
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
 *                   filters: ['book.exist_filter']
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
 *         <service id="book.exist_filter" parent="api_platform.doctrine.orm.exist_filter">
 *             <argument type="collection">
 *                 <argument key="comment"/>
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
 *                     <filter>book.exist_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books with the following query: `/books?exists[comment]=true`.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class ExistsFilter extends AbstractFilter implements ExistsFilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use ExistsFilterTrait;
    use PropertyPlaceholderOpenApiParameterTrait;

    public function __construct(?ManagerRegistry $managerRegistry = null, ?LoggerInterface $logger = null, ?array $properties = null, string $existsParameterName = self::QUERY_PARAMETER_KEY, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->existsParameterName = $existsParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;

        if (null !== ($value = $context['filters'][$parameter?->getProperty()] ?? null)) {
            $this->filterProperty($this->denormalizePropertyName($parameter->getProperty()), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

            return;
        }

        foreach ($context['filters'][$this->existsParameterName] ?? [] as $property => $value) {
            $this->filterProperty($this->denormalizePropertyName($property), $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass, true)
            || !$this->isNullableField($property, $resourceClass)
        ) {
            return;
        }

        $value = $this->normalizeValue($value, $property);
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasAssociation($field)) {
            if ($metadata->isCollectionValuedAssociation($field)) {
                $queryBuilder
                    ->andWhere(\sprintf('%s.%s %s EMPTY', $alias, $field, $value ? 'IS NOT' : 'IS'));

                return;
            }

            if ($metadata->isAssociationInverseSide($field)) {
                $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $field, Join::LEFT_JOIN);

                $queryBuilder
                    ->andWhere(\sprintf('%s %s NULL', $alias, $value ? 'IS NOT' : 'IS'));

                return;
            }

            $queryBuilder
                ->andWhere(\sprintf('%s.%s %s NULL', $alias, $field, $value ? 'IS NOT' : 'IS'));

            return;
        }

        if ($metadata->hasField($field)) {
            $queryBuilder
                ->andWhere(\sprintf('%s.%s %s NULL', $alias, $field, $value ? 'IS NOT' : 'IS'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isNullableField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        $field = $propertyParts['field'];

        if ($metadata->hasAssociation($field)) {
            if ($metadata->isSingleValuedAssociation($field)) {
                if (!($metadata instanceof ClassMetadata)) {
                    return false;
                }

                $associationMapping = $metadata->getAssociationMapping($field);

                return $this->isAssociationNullable($associationMapping);
            }

            return true;
        }

        if ($metadata instanceof ClassMetadata && $metadata->hasField($field)) {
            return $metadata->isNullable($field);
        }

        return false;
    }

    /**
     * Determines whether an association is nullable.
     *
     * @see https://github.com/doctrine/doctrine2/blob/v2.5.4/lib/Doctrine/ORM/Tools/EntityGenerator.php#L1221-L1246
     */
    private function isAssociationNullable(AssociationMapping|array $associationMapping): bool
    {
        if ($associationMapping instanceof AssociationMapping) {
            if (!empty($associationMapping->id)) {
                return false;
            }

            if ($associationMapping instanceof ToOneOwningSideMapping || $associationMapping instanceof ManyToManyOwningSideMapping) {
                foreach ($associationMapping->joinColumns as $joinColumn) {
                    if (false === $joinColumn->nullable) {
                        return false;
                    }
                }

                return true;
            }

            return true;
        }

        if (!empty($associationMapping['id'])) {
            return false;
        }

        if (!isset($associationMapping['joinColumns'])) {
            return true;
        }

        $joinColumns = $associationMapping['joinColumns'];
        foreach ($joinColumns as $joinColumn) {
            if (isset($joinColumn['nullable']) && !$joinColumn['nullable']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'boolean'];
    }
}
