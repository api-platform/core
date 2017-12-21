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

namespace ApiPlatform\Core\Graphql\Type;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Graphql\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Core\Graphql\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Doctrine\Common\Util\Inflector;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\PropertyInfo\Type;

/**
 * Builds the GraphQL schema.
 *
 * @experimental
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaBuilder implements SchemaBuilderInterface
{
    use ClassInfoTrait;

    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $collectionResolverFactory;
    private $itemResolver;
    private $itemMutationResolverFactory;
    private $defaultFieldResolver;
    private $paginationEnabled;
    private $graphqlTypes = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResolverFactoryInterface $collectionResolverFactory, ResolverFactoryInterface $itemMutationResolverFactory, callable $itemResolver, callable $defaultFieldResolver, bool $paginationEnabled = true)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionResolverFactory = $collectionResolverFactory;
        $this->itemResolver = $itemResolver;
        $this->itemMutationResolverFactory = $itemMutationResolverFactory;
        $this->defaultFieldResolver = $defaultFieldResolver;
        $this->paginationEnabled = $paginationEnabled;
    }

    public function getSchema(): Schema
    {
        $queryFields = ['node' => $this->getNodeQueryField()];
        $mutationFields = [];

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $graphqlConfiguration = $resourceMetadata->getGraphql() ?? [];
            foreach ($graphqlConfiguration as $operationName => $value) {
                if ('query' === $operationName) {
                    $queryFields += $this->getQueryFields($resourceClass, $resourceMetadata);

                    continue;
                }

                $mutationFields[$operationName.$resourceMetadata->getShortName()] = $this->getMutationFields($resourceClass, $resourceMetadata, $operationName);
            }
        }

        return new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $queryFields,
            ]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields,
            ]),
        ]);
    }

    private function getNodeInterface(): InterfaceType
    {
        if (isset($this->graphqlTypes['#node'])) {
            return $this->graphqlTypes['#node'];
        }

        return $this->graphqlTypes['#node'] = new InterfaceType([
            'name' => 'Node',
            'description' => 'A node, according to the Relay specification.',
            'fields' => [
                'id' => [
                    'type' => GraphQLType::nonNull(GraphQLType::id()),
                    'description' => 'The id of this node.',
                ],
            ],
            'resolveType' => function ($value) {
                if (!isset($value[ItemNormalizer::ITEM_KEY])) {
                    return null;
                }

                $resourceClass = $this->getObjectClass(unserialize($value[ItemNormalizer::ITEM_KEY]));

                return $this->graphqlTypes[$resourceClass][null][false] ?? null;
            },
        ]);
    }

    private function getNodeQueryField(): array
    {
        return [
            'type' => $this->getNodeInterface(),
            'args' => [
                'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
            ],
            'resolve' => $this->itemResolver,
        ];
    }

    /**
     * Gets the query fields of the schema.
     */
    private function getQueryFields(string $resourceClass, ResourceMetadata $resourceMetadata): array
    {
        $queryFields = [];
        $shortName = $resourceMetadata->getShortName();

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass), $resourceClass)) {
            $fieldConfiguration['args'] += ['id' => ['type' => GraphQLType::id()]];
            $queryFields[lcfirst($shortName)] = $fieldConfiguration;
        }

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, $resourceClass)), $resourceClass)) {
            $queryFields[lcfirst(Inflector::pluralize($shortName))] = $fieldConfiguration;
        }

        return $queryFields;
    }

    /**
     * Gets the mutation field for the given operation name.
     */
    private function getMutationFields(string $resourceClass, ResourceMetadata $resourceMetadata, string $mutationName): array
    {
        $shortName = $resourceMetadata->getShortName();
        $resourceType = new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass);

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(ucfirst("{$mutationName}s a $shortName."), $resourceType, $resourceClass, false, $mutationName)) {
            $fieldConfiguration['args'] += ['input' => $this->getResourceFieldConfiguration(null, $resourceType, $resourceClass, true, $mutationName)];

            if (!$resourceType->isCollection()) {
                $itemMutationResolverFactory = $this->itemMutationResolverFactory;
                $fieldConfiguration['resolve'] = $itemMutationResolverFactory($resourceClass, null, $mutationName);
            }
        }

        return $fieldConfiguration;
    }

    /**
     * Get the field configuration of a resource.
     *
     * @see http://webonyx.github.io/graphql-php/type-system/object-types/
     *
     * @return array|null
     */
    private function getResourceFieldConfiguration(string $fieldDescription = null, Type $type, string $rootResource, bool $input = false, string $mutationName = null)
    {
        try {
            if (null === $graphqlType = $this->convertType($type, $input, $mutationName)) {
                return null;
            }

            $graphqlWrappedType = $graphqlType instanceof WrappingType ? $graphqlType->getWrappedType() : $graphqlType;
            $isInternalGraphqlType = \in_array($graphqlWrappedType, GraphQLType::getInternalTypes(), true);
            if ($isInternalGraphqlType) {
                $className = '';
            } else {
                $className = $type->isCollection() ? $type->getCollectionValueType()->getClassName() : $type->getClassName();
            }

            $args = [];
            if ($this->paginationEnabled && !$isInternalGraphqlType && $type->isCollection() && !$input && null === $mutationName) {
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

            if ($isInternalGraphqlType || $input || null !== $mutationName) {
                $resolve = null;
            } elseif ($type->isCollection()) {
                $resolverFactory = $this->collectionResolverFactory;
                $resolve = $resolverFactory($className, $rootResource);
            } else {
                $resolve = $this->itemResolver;
            }

            return [
                'type' => $graphqlType,
                'description' => $fieldDescription,
                'args' => $args,
                'resolve' => $resolve,
            ];
        } catch (InvalidTypeException $e) {
            // just ignore invalid types
        }

        return null;
    }

    /**
     * Converts a built-in type to its GraphQL equivalent.
     *
     * @throws InvalidTypeException
     */
    private function convertType(Type $type, bool $input = false, string $mutationName = null)
    {
        $resourceClass = null;
        switch ($builtinType = $type->getBuiltinType()) {
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

                $resourceClass = $type->isCollection() ? $type->getCollectionValueType()->getClassName() : $type->getClassName();
                try {
                    $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                    if ([] === $resourceMetadata->getGraphql() ?? []) {
                        return null;
                    }
                } catch (ResourceClassNotFoundException $e) {
                    // Skip objects that are not resources for now
                    return null;
                }

                $graphqlType = $this->getResourceObjectType($resourceClass, $resourceMetadata, $input, $mutationName);
                break;
            default:
                throw new InvalidTypeException(sprintf('The type "%s" is not supported.', $builtinType));
        }

        if ($type->isCollection()) {
            return $this->paginationEnabled ? $this->getResourcePaginatedCollectionType($resourceClass, $graphqlType, $input) : GraphQLType::listOf($graphqlType);
        }

        return $type->isNullable() || (null !== $mutationName && 'update' === $mutationName) ? $graphqlType : GraphQLType::nonNull($graphqlType);
    }

    /**
     * Gets the object type of the given resource.
     *
     * @return ObjectType|InputObjectType
     */
    private function getResourceObjectType(string $resourceClass, ResourceMetadata $resourceMetadata, bool $input = false, string $mutationName = null): GraphQLType
    {
        $shortName = $resourceMetadata->getShortName();
        if ($input) {
            $shortName .= 'Input';
        }
        if (null !== $mutationName) {
            $shortName .= ucfirst($mutationName).'Mutation';
        }

        if (isset($this->graphqlTypes[$resourceClass][$mutationName][$input])) {
            return $this->graphqlTypes[$resourceClass][$mutationName][$input];
        }

        $configuration = [
            'name' => $shortName,
            'description' => $resourceMetadata->getDescription(),
            'resolveField' => $this->defaultFieldResolver,
            'fields' => function () use ($resourceClass, $input, $mutationName) {
                return $this->getResourceObjectTypeFields($resourceClass, $input, $mutationName);
            },
            'interfaces' => [$this->getNodeInterface()],
        ];

        return $this->graphqlTypes[$resourceClass][$mutationName][$input] = $input ? new InputObjectType($configuration) : new ObjectType($configuration);
    }

    /**
     * Gets the fields of the type of the given resource.
     */
    private function getResourceObjectTypeFields(string $resource, bool $input = false, string $mutationName = null): array
    {
        $fields = [];
        $idField = ['type' => GraphQLType::id()];

        if ('delete' === $mutationName) {
            return ['id' => $idField];
        }

        if (!$input || 'create' !== $mutationName) {
            $fields['id'] = $idField;
        }

        foreach ($this->propertyNameCollectionFactory->create($resource) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resource, $property);
            if (
                null === ($propertyType = $propertyMetadata->getType())
                || (!$input && null === $mutationName && !$propertyMetadata->isReadable())
                || (null !== $mutationName && !$propertyMetadata->isWritable())
            ) {
                continue;
            }

            if ($fieldConfiguration = $this->getResourceFieldConfiguration($propertyMetadata->getDescription(), $propertyType, $resource, $input, $mutationName)) {
                $fields['id' === $property ? '_id' : $property] = $fieldConfiguration;
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
    private function getResourcePaginatedCollectionType(string $resourceClass, GraphQLType $resourceType, bool $input = false): GraphQLType
    {
        $shortName = $resourceType->name;
        if ($input) {
            $shortName .= 'Input';
        }

        if (isset($this->graphqlTypes[$resourceClass]['connection'][$input])) {
            return $this->graphqlTypes[$resourceClass]['connection'][$input];
        }

        $edgeObjectTypeConfiguration = [
            'name' => "{$shortName}Edge",
            'description' => "Edge of $shortName.",
            'fields' => [
                'node' => $resourceType,
                'cursor' => GraphQLType::nonNull(GraphQLType::string()),
            ],
        ];
        $edgeObjectType = $input ? new InputObjectType($edgeObjectTypeConfiguration) : new ObjectType($edgeObjectTypeConfiguration);
        $pageInfoObjectTypeConfiguration = [
            'name' => "{$shortName}PageInfo",
            'description' => 'Information about the current page.',
            'fields' => [
                'endCursor' => GraphQLType::string(),
                'hasNextPage' => GraphQLType::nonNull(GraphQLType::boolean()),
            ],
        ];
        $pageInfoObjectType = $input ? new InputObjectType($pageInfoObjectTypeConfiguration) : new ObjectType($pageInfoObjectTypeConfiguration);

        $configuration = [
            'name' => "{$shortName}Connection",
            'description' => "Connection for $shortName.",
            'fields' => [
                'edges' => GraphQLType::listOf($edgeObjectType),
                'pageInfo' => GraphQLType::nonNull($pageInfoObjectType),
            ],
        ];

        return $this->graphqlTypes[$resourceClass]['connection'][$input] = $input ? new InputObjectType($configuration) : new ObjectType($configuration);
    }
}
