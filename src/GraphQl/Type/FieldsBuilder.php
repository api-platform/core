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

namespace ApiPlatform\GraphQl\Type;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Operation as AbstractOperation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\Util\Inflector;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\WrappingType;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Builds the GraphQL fields.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class FieldsBuilder implements FieldsBuilderInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceMetadataCollectionFactory;
    private $resourceClassResolver;
    private $typesContainer;
    private $typeBuilder;
    private $typeConverter;
    private $itemResolverFactory;
    private $collectionResolverFactory;
    private $itemMutationResolverFactory;
    private $itemSubscriptionResolverFactory;
    private $filterLocator;
    private $pagination;
    private $nameConverter;
    private $nestingSeparator;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ResourceClassResolverInterface $resourceClassResolver, TypesContainerInterface $typesContainer, TypeBuilderInterface $typeBuilder, TypeConverterInterface $typeConverter, ResolverFactoryInterface $itemResolverFactory, ResolverFactoryInterface $collectionResolverFactory, ResolverFactoryInterface $itemMutationResolverFactory, ResolverFactoryInterface $itemSubscriptionResolverFactory, ContainerInterface $filterLocator, Pagination $pagination, ?NameConverterInterface $nameConverter, string $nestingSeparator)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->typesContainer = $typesContainer;
        $this->typeBuilder = $typeBuilder;
        $this->typeConverter = $typeConverter;
        $this->itemResolverFactory = $itemResolverFactory;
        $this->collectionResolverFactory = $collectionResolverFactory;
        $this->itemMutationResolverFactory = $itemMutationResolverFactory;
        $this->itemSubscriptionResolverFactory = $itemSubscriptionResolverFactory;
        $this->filterLocator = $filterLocator;
        $this->pagination = $pagination;
        $this->nameConverter = $nameConverter;
        $this->nestingSeparator = $nestingSeparator;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeQueryFields(): array
    {
        return [
            'type' => $this->typeBuilder->getNodeInterface(),
            'args' => [
                'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
            ],
            'resolve' => ($this->itemResolverFactory)(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItemQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        $fieldName = lcfirst('item_query' === $operation->getName() ? $operation->getShortName() : $operation->getName().$operation->getShortName());

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $operation->getDescription(), $operation->getDeprecationReason(), new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass), $resourceClass, false, $operation)) {
            $args = $this->resolveResourceArgs($configuration['args'] ?? [], $operation);
            $configuration['args'] = $args ?: $configuration['args'] ?? ['id' => ['type' => GraphQLType::nonNull(GraphQLType::id())]];

            return [$fieldName => array_merge($fieldConfiguration, $configuration)];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        $fieldName = lcfirst('collection_query' === $operation->getName() ? $operation->getShortName() : $operation->getName().$operation->getShortName());

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $operation->getDescription(), $operation->getDeprecationReason(), new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, $resourceClass)), $resourceClass, false, $operation)) {
            $args = $this->resolveResourceArgs($configuration['args'] ?? [], $operation);
            $configuration['args'] = $args ?: $configuration['args'] ?? $fieldConfiguration['args'];

            return [Inflector::pluralize($fieldName) => array_merge($fieldConfiguration, $configuration)];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMutationFields(string $resourceClass, Operation $operation): array
    {
        $mutationFields = [];
        $resourceType = new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass);
        $description = $operation->getDescription() ?? ucfirst("{$operation->getName()}s a {$operation->getShortName()}.");

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $description, $operation->getDeprecationReason(), $resourceType, $resourceClass, false, $operation)) {
            $fieldConfiguration['args'] += ['input' => $this->getResourceFieldConfiguration(null, null, $operation->getDeprecationReason(), $resourceType, $resourceClass, true, $operation)];
        }

        $mutationFields[$operation->getName().$operation->getShortName()] = $fieldConfiguration ?? [];

        return $mutationFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionFields(string $resourceClass, Operation $operation): array
    {
        $subscriptionFields = [];
        $resourceType = new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass);
        $description = $operation->getDescription() ?? sprintf('Subscribes to the action event of a %s.', $operation->getShortName());

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $description, $operation->getDeprecationReason(), $resourceType, $resourceClass, false, $operation)) {
            $fieldConfiguration['args'] += ['input' => $this->getResourceFieldConfiguration(null, null, $operation->getDeprecationReason(), $resourceType, $resourceClass, true, $operation)];
        }

        if (!$fieldConfiguration) {
            return [];
        }

        $subscriptionName = $operation->getName();
        // TODO: 3.0 change this
        if ('update_subscription' === $subscriptionName) {
            $subscriptionName = 'update';
        }

        $subscriptionFields[$subscriptionName.$operation->getShortName().'Subscribe'] = $fieldConfiguration;

        return $subscriptionFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceObjectTypeFields(?string $resourceClass, Operation $operation, bool $input, int $depth = 0, ?array $ioMetadata = null): array
    {
        $fields = [];
        $idField = ['type' => GraphQLType::nonNull(GraphQLType::id())];
        $optionalIdField = ['type' => GraphQLType::id()];
        $clientMutationId = GraphQLType::string();
        $clientSubscriptionId = GraphQLType::string();

        if (null !== $ioMetadata && \array_key_exists('class', $ioMetadata) && null === $ioMetadata['class']) {
            if ($input) {
                return ['clientMutationId' => $clientMutationId];
            }

            return [];
        }

        if ($operation instanceof Subscription && $input) {
            return [
                'id' => $idField,
                'clientSubscriptionId' => $clientSubscriptionId,
            ];
        }

        if ('delete' === $operation->getName()) {
            $fields = [
                'id' => $idField,
            ];

            if ($input) {
                $fields['clientMutationId'] = $clientMutationId;
            }

            return $fields;
        }

        if (!$input || 'create' !== $operation->getName()) {
            $fields['id'] = $idField;
        }
        if ($input && $depth >= 1) {
            $fields['id'] = $optionalIdField;
        }

        ++$depth; // increment the depth for the call to getResourceFieldConfiguration.

        if (null !== $resourceClass) {
            foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
                $context = [
                    'normalization_groups' => $operation->getNormalizationContext()['groups'] ?? null,
                    'denormalization_groups' => $operation->getDenormalizationContext()['groups'] ?? null,
                ];
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property, $context);

                if (
                    null === ($propertyType = $propertyMetadata->getBuiltinTypes()[0] ?? null)
                    || (!$input && false === $propertyMetadata->isReadable())
                    || ($input && $operation instanceof Mutation && false === $propertyMetadata->isWritable())
                ) {
                    continue;
                }

                if ($fieldConfiguration = $this->getResourceFieldConfiguration($property, $propertyMetadata->getDescription(), $propertyMetadata->getDeprecationReason(), $propertyType, $resourceClass, $input, $operation, $depth, null !== $propertyMetadata->getSecurity())) {
                    $fields['id' === $property ? '_id' : $this->normalizePropertyName($property, $resourceClass)] = $fieldConfiguration;
                }
            }
        }

        if ($operation instanceof Mutation && $input) {
            $fields['clientMutationId'] = $clientMutationId;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveResourceArgs(array $args, Operation $operation): array
    {
        foreach ($args as $id => $arg) {
            if (!isset($arg['type'])) {
                throw new \InvalidArgumentException(sprintf('The argument "%s" of the custom operation "%s" in %s needs a "type" option.', $id, $operation->getName(), $operation->getShortName()));
            }

            $args[$id]['type'] = $this->typeConverter->resolveType($arg['type']);
        }

        return $args;
    }

    /**
     * Get the field configuration of a resource.
     *
     * @see http://webonyx.github.io/graphql-php/type-system/object-types/
     */
    private function getResourceFieldConfiguration(?string $property, ?string $fieldDescription, ?string $deprecationReason, Type $type, string $rootResource, bool $input, Operation $rootOperation, int $depth = 0, bool $forceNullable = false): ?array
    {
        try {
            $isCollectionType = $this->typeBuilder->isCollection($type);

            if (
                $isCollectionType &&
                $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()
            ) {
                $resourceClass = $collectionValueType->getClassName();
            } else {
                $resourceClass = $type->getClassName();
            }

            if (null === $graphqlType = $this->convertType($type, $input, $rootOperation, $resourceClass ?? '', $rootResource, $property, $depth, $forceNullable)) {
                return null;
            }

            $graphqlWrappedType = $graphqlType instanceof WrappingType ? $graphqlType->getWrappedType(true) : $graphqlType;
            $isStandardGraphqlType = \in_array($graphqlWrappedType, GraphQLType::getStandardTypes(), true);
            if ($isStandardGraphqlType) {
                $resourceClass = '';
            }

            // Check mercure attribute if it's a subscription at the root level.
            if ($rootOperation instanceof Subscription && null === $property && !$rootOperation->getMercure()) {
                return null;
            }

            $args = [];

            $resolverOperation = $rootOperation;

            if ($resourceClass && $this->resourceClassResolver->isResourceClass($resourceClass) && $rootOperation->getClass() !== $resourceClass) {
                $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
                $resolverOperation = $resourceMetadataCollection->getOperation(null, $isCollectionType);

                if (!$resolverOperation instanceof Operation) {
                    $resolverOperation = ($isCollectionType ? new QueryCollection() : new Query())->withOperation($resolverOperation);
                }
            }

            if (!$input && !$rootOperation instanceof Mutation && !$rootOperation instanceof Subscription && !$isStandardGraphqlType && $isCollectionType) {
                if ($this->pagination->isGraphQlEnabled($rootOperation)) {
                    $args = $this->getGraphQlPaginationArgs($rootOperation);
                }

                // Find the collection operation to get filters, there might be a smarter way to do this
                $operation = null;
                if (!empty($resourceClass)) {
                    $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
                    try {
                        $operation = $resourceMetadataCollection->getOperation(null, true);
                    } catch (OperationNotFoundException $e) {
                    }
                }

                $args = $this->getFilterArgs($args, $resourceClass, $rootResource, $rootOperation, $property, $depth, $operation);
            }

            if ($isStandardGraphqlType || $input) {
                $resolve = null;
            } elseif (($rootOperation instanceof Mutation || $rootOperation instanceof Subscription) && $depth <= 0) {
                $resolve = $rootOperation instanceof Mutation ? ($this->itemMutationResolverFactory)($resourceClass, $rootResource, $resolverOperation) : ($this->itemSubscriptionResolverFactory)($resourceClass, $rootResource, $resolverOperation);
            } elseif ($this->typeBuilder->isCollection($type)) {
                $resolve = ($this->collectionResolverFactory)($resourceClass, $rootResource, $resolverOperation);
            } else {
                $resolve = ($this->itemResolverFactory)($resourceClass, $rootResource, $resolverOperation);
            }

            return [
                'type' => $graphqlType,
                'description' => $fieldDescription,
                'args' => $args,
                'resolve' => $resolve,
                'deprecationReason' => $deprecationReason,
            ];
        } catch (InvalidTypeException $e) {
            // just ignore invalid types
        }

        return null;
    }

    private function getGraphQlPaginationArgs(Operation $queryOperation): array
    {
        $paginationType = $this->pagination->getGraphQlPaginationType($queryOperation);

        if ('cursor' === $paginationType) {
            return [
                'first' => [
                    'type' => GraphQLType::int(),
                    'description' => 'Returns the first n elements from the list.',
                ],
                'last' => [
                    'type' => GraphQLType::int(),
                    'description' => 'Returns the last n elements from the list.',
                ],
                'before' => [
                    'type' => GraphQLType::string(),
                    'description' => 'Returns the elements in the list that come before the specified cursor.',
                ],
                'after' => [
                    'type' => GraphQLType::string(),
                    'description' => 'Returns the elements in the list that come after the specified cursor.',
                ],
            ];
        }

        $paginationOptions = $this->pagination->getOptions();

        $args = [
            $paginationOptions['page_parameter_name'] => [
                'type' => GraphQLType::int(),
                'description' => 'Returns the current page.',
            ],
        ];

        if ($paginationOptions['client_items_per_page']) {
            $args[$paginationOptions['items_per_page_parameter_name']] = [
                'type' => GraphQLType::int(),
                'description' => 'Returns the number of items per page.',
            ];
        }

        return $args;
    }

    private function getFilterArgs(array $args, ?string $resourceClass, string $rootResource, Operation $rootOperation, ?string $property, int $depth, ?AbstractOperation $operation = null): array
    {
        if (null === $operation || null === $resourceClass) {
            return $args;
        }

        foreach ($operation->getFilters() ?? [] as $filterId) {
            if (null === $this->filterLocator || !$this->filterLocator->has($filterId)) {
                continue;
            }

            foreach ($this->filterLocator->get($filterId)->getDescription($resourceClass) as $key => $value) {
                $nullable = isset($value['required']) ? !$value['required'] : true;
                $filterType = \in_array($value['type'], Type::$builtinTypes, true) ? new Type($value['type'], $nullable) : new Type('object', $nullable, $value['type']);
                $graphqlFilterType = $this->convertType($filterType, false, $rootOperation, $resourceClass, $rootResource, $property, $depth);

                if ('[]' === substr($key, -2)) {
                    $graphqlFilterType = GraphQLType::listOf($graphqlFilterType);
                    $key = substr($key, 0, -2).'_list';
                }

                /** @var string $key */
                $key = str_replace('.', $this->nestingSeparator, $key);

                parse_str($key, $parsed);
                if (\array_key_exists($key, $parsed) && \is_array($parsed[$key])) {
                    $parsed = [$key => ''];
                }
                array_walk_recursive($parsed, static function (&$value) use ($graphqlFilterType) {
                    $value = $graphqlFilterType;
                });
                $args = $this->mergeFilterArgs($args, $parsed, $operation, $key);
            }
        }

        return $this->convertFilterArgsToTypes($args);
    }

    /**
     * @param AbstractOperation|null $operation
     * @param mixed                  $original
     */
    private function mergeFilterArgs(array $args, array $parsed, $operation = null, $original = ''): array
    {
        foreach ($parsed as $key => $value) {
            // Never override keys that cannot be merged
            if (isset($args[$key]) && !\is_array($args[$key])) {
                continue;
            }

            if (\is_array($value)) {
                $value = $this->mergeFilterArgs($args[$key] ?? [], $value);
                if (!isset($value['#name'])) {
                    $name = (false === $pos = strrpos($original, '[')) ? $original : substr($original, 0, (int) $pos);
                    $value['#name'] = ($operation ? $operation->getShortName() : '').'Filter_'.strtr($name, ['[' => '_', ']' => '', '.' => '__']);
                }
            }

            $args[$key] = $value;
        }

        return $args;
    }

    private function convertFilterArgsToTypes(array $args): array
    {
        foreach ($args as $key => $value) {
            if (strpos($key, '.')) {
                // Declare relations/nested fields in a GraphQL compatible syntax.
                $args[str_replace('.', $this->nestingSeparator, $key)] = $value;
                unset($args[$key]);
            }
        }

        foreach ($args as $key => $value) {
            if (!\is_array($value) || !isset($value['#name'])) {
                continue;
            }

            $name = $value['#name'];

            if ($this->typesContainer->has($name)) {
                $args[$key] = $this->typesContainer->get($name);
                continue;
            }

            unset($value['#name']);

            $filterArgType = GraphQLType::listOf(new InputObjectType([
                'name' => $name,
                'fields' => $this->convertFilterArgsToTypes($value),
            ]));

            $this->typesContainer->set($name, $filterArgType);

            $args[$key] = $filterArgType;
        }

        return $args;
    }

    /**
     * Converts a built-in type to its GraphQL equivalent.
     *
     * @throws InvalidTypeException
     */
    private function convertType(Type $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth, bool $forceNullable = false)
    {
        $graphqlType = $this->typeConverter->convertType($type, $input, $rootOperation, $resourceClass, $rootResource, $property, $depth);

        if (null === $graphqlType) {
            throw new InvalidTypeException(sprintf('The type "%s" is not supported.', $type->getBuiltinType()));
        }

        if (\is_string($graphqlType)) {
            if (!$this->typesContainer->has($graphqlType)) {
                throw new InvalidTypeException(sprintf('The GraphQL type %s is not valid. Valid types are: %s. Have you registered this type by implementing %s?', $graphqlType, implode(', ', array_keys($this->typesContainer->all())), TypeInterface::class));
            }

            $graphqlType = $this->typesContainer->get($graphqlType);
        }

        if ($this->typeBuilder->isCollection($type)) {
            return $this->pagination->isGraphQlEnabled($rootOperation) && !$input ? $this->typeBuilder->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $rootOperation) : GraphQLType::listOf($graphqlType);
        }

        return $forceNullable || !$graphqlType instanceof NullableType || $type->isNullable() || ($rootOperation instanceof Mutation && 'update' === $rootOperation->getName())
            ? $graphqlType
            : GraphQLType::nonNull($graphqlType);
    }

    private function normalizePropertyName(string $property, string $resourceClass): string
    {
        if (null === $this->nameConverter) {
            return $property;
        }
        if ($this->nameConverter instanceof AdvancedNameConverterInterface) {
            return $this->nameConverter->normalize($property, $resourceClass);
        }

        return $this->nameConverter->normalize($property);
    }
}
