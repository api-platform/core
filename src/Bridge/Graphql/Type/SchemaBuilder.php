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

namespace ApiPlatform\Core\Bridge\Graphql\Type;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Graphql\Resolver\CollectionResolverFactoryInterface;
use ApiPlatform\Core\Bridge\Graphql\Resolver\ItemResolverFactoryInterface;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Util\Inflector;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Type;

/**
 * Builder of the GraphQL schema.
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
final class SchemaBuilder implements SchemaBuilderInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $collectionResolverFactory;
    private $itemResolverFactory;
    private $identifiersExtractor;
    private $paginationEnabled;
    private $resourceTypesCache = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, CollectionResolverFactoryInterface $collectionResolverFactory, ItemResolverFactoryInterface $itemResolverFactory, IdentifiersExtractorInterface $identifiersExtractor, bool $paginationEnabled)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionResolverFactory = $collectionResolverFactory;
        $this->itemResolverFactory = $itemResolverFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->paginationEnabled = $paginationEnabled;
    }

    public function getSchema(): Schema
    {
        $queryFields = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resource) {
            $queryFields += $this->getQueryFields($resource);
        }

        return new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $queryFields,
            ]),
        ]);
    }

    /**
     * Gets the query fields of the schema.
     */
    private function getQueryFields(string $resource): array
    {
        $queryFields = [];
        $resourceMetadata = $this->resourceMetadataFactory->create($resource);
        $shortName = $resourceMetadata->getShortName();

        foreach ($this->getOperations($resourceMetadata, true, true) as $operationName => $queryItemOperation) {
            $fieldNamePrefix = 'get' === $operationName ? '' : $operationName;
            if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, new Type(Type::BUILTIN_TYPE_OBJECT, true, $resource), $resource)) {
                $fieldConfiguration['args'] += $this->getResourceIdentifiersArgumentsConfiguration($resource);
                $queryFields[lcfirst($fieldNamePrefix.$shortName)] = $fieldConfiguration;
            }
        }

        foreach ($this->getOperations($resourceMetadata, true, false) as $operationName => $queryCollectionOperation) {
            $fieldNamePrefix = 'get' === $operationName ? '' : $operationName;
            if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, $resource)), $resource)) {
                $queryFields[lcfirst($fieldNamePrefix.Inflector::pluralize($shortName))] = $fieldConfiguration;
            }
        }

        return $queryFields;
    }

    /**
     * Get the field configuration of a resource.
     *
     * @see http://webonyx.github.io/graphql-php/type-system/object-types/
     *
     * @return array|null
     */
    private function getResourceFieldConfiguration(string $fieldDescription = null, Type $type, string $rootResource, bool $isInput = false)
    {
        try {
            $graphqlType = $this->convertType($type, $isInput);
            $graphqlWrappedType = $graphqlType instanceof WrappingType ? $graphqlType->getWrappedType() : $graphqlType;
            $isInternalGraphqlType = in_array($graphqlWrappedType, GraphQLType::getInternalTypes(), true);
            $className = $isInternalGraphqlType ? '' : ($type->isCollection() ? $type->getCollectionValueType()->getClassName() : $type->getClassName());

            $args = [];
            if ($this->paginationEnabled && !$isInternalGraphqlType && $type->isCollection() && !$isInput) {
                $args = [
                    'first' => [
                        'type' => GraphQLType::int(),
                        'description' => 'Returns the first n elements from the list.',
                    ],
                    'after' => [
                        'type' => GraphQLType::string(),
                        'description' => 'Returns the elements in the list that come after the specified cursor.',
                    ],
                ];
            }

            if ($isInternalGraphqlType || $isInput) {
                $resolve = null;
            } else {
                $resolve = $type->isCollection() ? $this->collectionResolverFactory->createCollectionResolver($className, $rootResource) : $this->itemResolverFactory->createItemResolver($className, $rootResource);
            }

            return [
                'type' => $graphqlType,
                'description' => $fieldDescription,
                'args' => $args,
                'resolve' => $resolve,
            ];
        } catch (InvalidTypeException $e) {
            return null;
        }
    }

    /**
     * Gets the field arguments of the identifier of a given resource.
     *
     * @throws \LogicException
     */
    private function getResourceIdentifiersArgumentsConfiguration(string $resource): array
    {
        $arguments = [];
        $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($resource);
        foreach ($identifiers as $identifier) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resource, $identifier);
            $propertyType = $propertyMetadata->getType();
            if (null === $propertyType) {
                continue;
            }

            $arguments[$identifier] = $this->getResourceFieldConfiguration($propertyMetadata->getDescription(), $propertyType, $resource, true);
        }
        if (!$arguments) {
            throw new \LogicException("Missing identifier field for resource \"$resource\".");
        }

        return $arguments;
    }

    /**
     * Converts a built-in type to its GraphQL equivalent.
     *
     * @throws InvalidTypeException
     */
    private function convertType(Type $type, bool $isInput = false): GraphQLType
    {
        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_BOOL:
                $graphqlType = GraphQLType::boolean();
                break;
            case Type::BUILTIN_TYPE_INT:
                $graphqlType = GraphQLType::int();
                break;
            case Type::BUILTIN_TYPE_FLOAT:
                $graphqlType = GraphQLType::float();
                break;
            case Type::BUILTIN_TYPE_STRING:
                $graphqlType = GraphQLType::string();
                break;
            case Type::BUILTIN_TYPE_OBJECT:
                if (is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                    $graphqlType = GraphQLType::string();
                    break;
                }

                try {
                    $className = $type->isCollection() ? $type->getCollectionValueType()->getClassName() : $type->getClassName();
                    $resourceMetadata = $this->resourceMetadataFactory->create($className);
                } catch (ResourceClassNotFoundException $e) {
                    throw new InvalidTypeException();
                }

                $graphqlType = $this->getResourceObjectType($className, $resourceMetadata, $isInput);
                break;
            default:
                throw new InvalidTypeException();
        }

        if ($type->isCollection()) {
            return $this->paginationEnabled ? $this->getResourcePaginatedCollectionType($graphqlType, $isInput) : GraphQLType::listOf($graphqlType);
        }

        return $type->isNullable() ? $graphqlType : GraphQLType::nonNull($graphqlType);
    }

    /**
     * Gets the object type of the given resource.
     *
     * @return ObjectType|InputObjectType
     */
    private function getResourceObjectType(string $resource, ResourceMetadata $resourceMetadata, bool $isInput = false)
    {
        $shortName = $resourceMetadata->getShortName().($isInput ? 'Input' : '');

        if (isset($this->resourceTypesCache[$shortName])) {
            return $this->resourceTypesCache[$shortName];
        }

        $configuration = [
            'name' => $shortName,
            'description' => $resourceMetadata->getDescription(),
            'fields' => function () use ($resource, $isInput) {
                return $this->getResourceObjectTypeFields($resource, $isInput);
            },
        ];

        return $this->resourceTypesCache[$shortName] = $isInput ? new InputObjectType($configuration) : new ObjectType($configuration);
    }

    /**
     * Gets the fields of the type of the given resource.
     */
    private function getResourceObjectTypeFields(string $resource, bool $isInput = false): array
    {
        $fields = [];

        foreach ($this->propertyNameCollectionFactory->create($resource) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resource, $property);
            if (null === ($propertyType = $propertyMetadata->getType())
                || !$propertyMetadata->isReadable()) {
                continue;
            }

            if ($fieldConfiguration = $this->getResourceFieldConfiguration($propertyMetadata->getDescription(), $propertyType, $resource, $isInput)) {
                $fields[$property] = $fieldConfiguration;
            }
        }

        return $fields;
    }

    /**
     * Gets the type of a paginated collection of the given resource type.
     *
     * @param ObjectType|InputObjectType $resourceType
     *
     * @return ObjectType|InputObjectType
     */
    private function getResourcePaginatedCollectionType($resourceType, bool $isInput = false)
    {
        $shortName = $resourceType->name.($isInput ? 'Input' : '');

        if (isset($this->resourceTypesCache["{$shortName}Connection"])) {
            return $this->resourceTypesCache["{$shortName}Connection"];
        }

        $edgeObjectTypeConfiguration = [
            'name' => $shortName.'Edge',
            'description' => "Edge of $shortName.",
            'fields' => [
                'node' => $resourceType,
                'cursor' => GraphQLType::nonNull(GraphQLType::string()),
            ],
        ];
        $edgeObjectType = $isInput ? new InputObjectType($edgeObjectTypeConfiguration) : new ObjectType($edgeObjectTypeConfiguration);
        $pageInfoObjectTypeConfiguration = [
            'name' => $shortName.'PageInfo',
            'description' => 'Information about the current page.',
            'fields' => [
                'endCursor' => GraphQLType::string(),
                'hasNextPage' => GraphQLType::nonNull(GraphQLType::boolean()),
            ],
        ];
        $pageInfoObjectType = $isInput ? new InputObjectType($pageInfoObjectTypeConfiguration) : new ObjectType($pageInfoObjectTypeConfiguration);

        $configuration = [
            'name' => $shortName.'Connection',
            'description' => "Connection for $shortName.",
            'fields' => [
                'edges' => GraphQLType::listOf($edgeObjectType),
                'pageInfo' => GraphQLType::nonNull($pageInfoObjectType),
            ],
        ];

        return $this->resourceTypesCache[$shortName.'Connection'] = $isInput ? new InputObjectType($configuration) : new ObjectType($configuration);
    }

    /**
     * Get the available operations for a resource.
     */
    private function getOperations(ResourceMetadata $resourceMetadata, bool $isQuery, bool $isItem): \Traversable
    {
        $operations = $isItem ? $resourceMetadata->getItemOperations() : $resourceMetadata->getCollectionOperations();
        if (null === $operations) {
            return yield from [];
        }

        foreach ($operations as $operationName => $operation) {
            if (isset($operation['controller']) || !isset($operation['method'])) {
                continue;
            }

            if ($isQuery && Request::METHOD_GET !== $operation['method'] || !$isQuery && Request::METHOD_GET === $operation['method']) {
                continue;
            }

            yield $operationName => $operation;
        }
    }
}
