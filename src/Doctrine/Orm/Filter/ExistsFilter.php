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
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\NameConverterAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\NameConverterAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterTrait;
use ApiPlatform\Doctrine\Common\Filter\PropertyPlaceholderOpenApiParameterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\ToOneOwningSideMapping;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata as LegacyClassMetadata;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
final class ExistsFilter implements ExistsFilterInterface, FilterInterface, JsonSchemaFilterInterface, ManagerRegistryAwareInterface, NameConverterAwareInterface, OpenApiParameterFilterInterface, PropertyAwareFilterInterface
{
    use ExistsFilterTrait;
    use ManagerRegistryAwareTrait;
    use NameConverterAwareTrait;
    use PropertyAwareFilterTrait;
    use PropertyPlaceholderOpenApiParameterTrait;

    private LoggerInterface $logger;

    /**
     * Resolved from the QueryBuilder in apply(); metadata is read from it so the active filter path
     * never touches the injected ManagerRegistry (kept only for the deprecated getDescription() and
     * for BC injection through ManagerRegistryAwareInterface).
     */
    private ?EntityManagerInterface $entityManager = null;

    /**
     * @param array<string, mixed>|null $properties
     */
    public function __construct(?ManagerRegistry $managerRegistry = null, ?LoggerInterface $logger = null, ?array $properties = null, string $existsParameterName = self::QUERY_PARAMETER_KEY, ?NameConverterInterface $nameConverter = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger ?? new NullLogger();
        $this->existsParameterName = $existsParameterName;
        $this->properties = $properties;
        $this->nameConverter = $nameConverter;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        if (null === $this->properties) {
            // to ensure sanity, nested properties must still be explicitly enabled
            return !$this->isPropertyNested($property, $resourceClass);
        }

        return \array_key_exists($property, $this->properties);
    }

    protected function getClassMetadata(string $resourceClass): LegacyClassMetadata
    {
        if ($this->entityManager instanceof EntityManagerInterface) {
            return $this->entityManager->getClassMetadata($resourceClass);
        }

        // Legacy getDescription() runs without a QueryBuilder: fall back to the injected registry.
        if ($this->hasManagerRegistry() && ($manager = $this->getManagerRegistry()->getManagerForClass($resourceClass))) {
            return $manager->getClassMetadata($resourceClass);
        }

        return new ClassMetadata($resourceClass);
    }

    /**
     * @return array{0: string, 1: string, 2: string[]}
     */
    protected function addJoinsForNestedProperty(string $property, string $rootAlias, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $joinType): array
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $parentAlias = $rootAlias;
        $alias = null;

        foreach ($propertyParts['associations'] as $association) {
            $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $parentAlias, $association, $joinType);
            $parentAlias = $alias;
        }

        if (null === $alias) {
            throw new InvalidArgumentException(\sprintf('Cannot add joins for property "%s" - property is not nested.', $property));
        }

        return [$alias, $propertyParts['field'], $propertyParts['associations']];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->entityManager = $queryBuilder->getEntityManager();
        $parameter = $context['parameter'] ?? null;
        $propertyKey = $parameter?->getProperty();

        if (null !== $propertyKey && null !== ($value = $context['filters'][$propertyKey] ?? null)) {
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
                if (!$metadata instanceof ClassMetadata) {
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
