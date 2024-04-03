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

use ApiPlatform\Doctrine\Odm\State\Options as ODMOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\Pagination\Pagination;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
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
final class FieldsBuilder implements FieldsBuilderInterface, FieldsBuilderEnumInterface
{
    private readonly ContextAwareTypeBuilderInterface|TypeBuilderEnumInterface|TypeBuilderInterface $typeBuilder;

    public function __construct(private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ResourceClassResolverInterface $resourceClassResolver, private readonly TypesContainerInterface $typesContainer, ContextAwareTypeBuilderInterface|TypeBuilderEnumInterface|TypeBuilderInterface $typeBuilder, private readonly TypeConverterInterface $typeConverter, private readonly ResolverFactoryInterface $itemResolverFactory, private readonly ?ResolverFactoryInterface $collectionResolverFactory, private readonly ?ResolverFactoryInterface $itemMutationResolverFactory, private readonly ?ResolverFactoryInterface $itemSubscriptionResolverFactory, private readonly ContainerInterface $filterLocator, private readonly Pagination $pagination, private readonly ?NameConverterInterface $nameConverter, private readonly string $nestingSeparator)
    {
        if ($typeBuilder instanceof TypeBuilderInterface) {
            @trigger_error(sprintf('$typeBuilder argument of FieldsBuilder implementing "%s" is deprecated since API Platform 3.1. It has to implement "%s" instead.', TypeBuilderInterface::class, TypeBuilderEnumInterface::class), \E_USER_DEPRECATED);
        }
        if ($typeBuilder instanceof TypeBuilderEnumInterface) {
            @trigger_error(sprintf('$typeBuilder argument of TypeConverter implementing "%s" is deprecated since API Platform 3.3. It has to implement "%s" instead.', TypeBuilderEnumInterface::class, ContextAwareTypeBuilderInterface::class), \E_USER_DEPRECATED);
        }
        $this->typeBuilder = $typeBuilder;
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
        if ($operation instanceof Query && $operation->getNested()) {
            return [];
        }

        $fieldName = lcfirst('item_query' === $operation->getName() ? $operation->getShortName() : $operation->getName().$operation->getShortName());

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $operation->getDescription(), $operation->getDeprecationReason(), new Type(Type::BUILTIN_TYPE_OBJECT, true, $resourceClass), $resourceClass, false, $operation)) {
            $args = $this->resolveResourceArgs($configuration['args'] ?? [], $operation);
            $extraArgs = $this->resolveResourceArgs($operation->getExtraArgs() ?? [], $operation);
            $configuration['args'] = $args ?: $configuration['args'] ?? ['id' => ['type' => GraphQLType::nonNull(GraphQLType::id())]] + $extraArgs;

            return [$fieldName => array_merge($fieldConfiguration, $configuration)];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        if ($operation instanceof Query && $operation->getNested()) {
            return [];
        }

        $fieldName = lcfirst('collection_query' === $operation->getName() ? $operation->getShortName() : $operation->getName().$operation->getShortName());

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $operation->getDescription(), $operation->getDeprecationReason(), new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, $resourceClass)), $resourceClass, false, $operation)) {
            $args = $this->resolveResourceArgs($configuration['args'] ?? [], $operation);
            $extraArgs = $this->resolveResourceArgs($operation->getExtraArgs() ?? [], $operation);
            $configuration['args'] = $args ?: $configuration['args'] ?? $fieldConfiguration['args'] + $extraArgs;

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

        if (!$input || (!$operation->getResolver() && 'create' !== $operation->getName())) {
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
                $propertyTypes = $propertyMetadata->getBuiltinTypes();

                if (
                    !$propertyTypes
                    || (!$input && false === $propertyMetadata->isReadable())
                    || ($input && false === $propertyMetadata->isWritable())
                ) {
                    continue;
                }

                // guess union/intersect types: check each type until finding a valid one
                foreach ($propertyTypes as $propertyType) {
                    if ($fieldConfiguration = $this->getResourceFieldConfiguration($property, $propertyMetadata->getDescription(), $propertyMetadata->getDeprecationReason(), $propertyType, $resourceClass, $input, $operation, $depth, null !== $propertyMetadata->getSecurity())) {
                        $fields['id' === $property ? '_id' : $this->normalizePropertyName($property, $resourceClass)] = $fieldConfiguration;
                        // stop at the first valid type
                        break;
                    }
                }
            }
        }

        if ($operation instanceof Mutation && $input) {
            $fields['clientMutationId'] = $clientMutationId;
        }

        return $fields;
    }

    private function isEnumClass(string $resourceClass): bool
    {
        return is_a($resourceClass, \BackedEnum::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnumFields(string $enumClass): array
    {
        $rEnum = new \ReflectionEnum($enumClass);

        $enumCases = [];
        /* @var \ReflectionEnumUnitCase|\ReflectionEnumBackedCase */
        foreach ($rEnum->getCases() as $rCase) {
            if ($rCase instanceof \ReflectionEnumBackedCase) {
                $enumCase = ['value' => $rCase->getBackingValue()];
            } else {
                $enumCase = ['value' => $rCase->getValue()];
            }

            $propertyMetadata = $this->propertyMetadataFactory->create($enumClass, $rCase->getName());
            if ($enumCaseDescription = $propertyMetadata->getDescription()) {
                $enumCase['description'] = $enumCaseDescription;
            }
            $enumCases[$rCase->getName()] = $enumCase;
        }

        return $enumCases;
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

        /*
         * This is @experimental, read the comment on the parameterToObjectType function as additional information.
         */
        foreach ($operation->getParameters() ?? [] as $parameter) {
            $key = $parameter->getKey();

            if (str_contains($key, ':property')) {
                if (!($filterId = $parameter->getFilter()) || !$this->filterLocator->has($filterId)) {
                    continue;
                }

                $parsedKey = explode('[:property]', $key);
                $flattenFields = [];
                foreach ($this->filterLocator->get($filterId)->getDescription($operation->getClass()) as $key => $value) {
                    $values = [];
                    parse_str($key, $values);
                    if (isset($values[$parsedKey[0]])) {
                        $values = $values[$parsedKey[0]];
                    }

                    $name = key($values);
                    $flattenFields[] = ['name' => $name, 'required' => $value['required'] ?? null, 'description' => $value['description'] ?? null, 'leafs' => $values[$name], 'type' => $value['type'] ?? 'string'];
                }

                $args[$parsedKey[0]] = $this->parameterToObjectType($flattenFields, $parsedKey[0]);
                continue;
            }

            $args[$key] = ['type' => GraphQLType::string()];

            if ($parameter->getRequired()) {
                $args[$key]['type'] = GraphQLType::nonNull($args[$key]['type']);
            }
        }

        return $args;
    }

    /**
     * Transform the result of a parse_str to a GraphQL object type.
     * We should consider merging getFilterArgs and this, `getFilterArgs` uses `convertType` whereas we assume that parameters have only scalar types.
     * Note that this method has a lower complexity then the `getFilterArgs` one.
     * TODO: Is there a use case with an argument being a complex type (eg: a Resource, Enum etc.)?
     *
     * @param array<array{name: string, required: bool|null, description: string|null, leafs: string|array, type: string}> $flattenFields
     */
    private function parameterToObjectType(array $flattenFields, string $name): InputObjectType
    {
        $fields = [];
        foreach ($flattenFields as $field) {
            $key = $field['name'];
            $type = $this->getParameterType(\in_array($field['type'], Type::$builtinTypes, true) ? new Type($field['type'], !$field['required']) : new Type('object', !$field['required'], $field['type']));

            if (\is_array($l = $field['leafs'])) {
                if (0 === key($l)) {
                    $key = $key;
                    $type = GraphQLType::listOf($type);
                } else {
                    $n = [];
                    foreach ($field['leafs'] as $l => $value) {
                        $n[] = ['required' => null, 'name' => $l, 'leafs' => $value, 'type' => 'string', 'description' => null];
                    }

                    $type = $this->parameterToObjectType($n, $key);
                    if (isset($fields[$key]) && ($t = $fields[$key]['type']) instanceof InputObjectType) {
                        $t = $fields[$key]['type'];
                        $t->config['fields'] = array_merge($t->config['fields'], $type->config['fields']);
                        $type = $t;
                    }
                }
            }

            if ($field['required']) {
                $type = GraphQLType::nonNull($type);
            }

            if (isset($fields[$key])) {
                if ($type instanceof ListOfType) {
                    $key .= '_list';
                }
            }

            $fields[$key] = ['type' => $type, 'name' => $key];
        }

        return new InputObjectType(['name' => $name, 'fields' => $fields]);
    }

    /**
     * A simplified version of convert type that does not support resources.
     */
    private function getParameterType(Type $type): GraphQLType
    {
        return match ($type->getBuiltinType()) {
            Type::BUILTIN_TYPE_BOOL => GraphQLType::boolean(),
            Type::BUILTIN_TYPE_INT => GraphQLType::int(),
            Type::BUILTIN_TYPE_FLOAT => GraphQLType::float(),
            Type::BUILTIN_TYPE_STRING => GraphQLType::string(),
            Type::BUILTIN_TYPE_ARRAY => GraphQLType::listOf($this->getParameterType($type->getCollectionValueTypes()[0])),
            Type::BUILTIN_TYPE_ITERABLE => GraphQLType::listOf($this->getParameterType($type->getCollectionValueTypes()[0])),
            Type::BUILTIN_TYPE_OBJECT => GraphQLType::string(),
            default => GraphQLType::string(),
        };
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
                $isCollectionType
                && $collectionValueType = $type->getCollectionValueTypes()[0] ?? null
            ) {
                $resourceClass = $collectionValueType->getClassName();
            } else {
                $resourceClass = $type->getClassName();
            }

            $resourceOperation = $rootOperation;
            if ($resourceClass && $depth >= 1 && $this->resourceClassResolver->isResourceClass($resourceClass)) {
                $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
                $resourceOperation = $resourceMetadataCollection->getOperation($isCollectionType ? 'collection_query' : 'item_query');
            }

            if (!$resourceOperation instanceof Operation) {
                throw new \LogicException('The resource operation should be a GraphQL operation.');
            }

            $graphqlType = $this->convertType($type, $input, $resourceOperation, $rootOperation, $resourceClass ?? '', $rootResource, $property, $depth, $forceNullable);

            $graphqlWrappedType = $graphqlType;
            if ($graphqlType instanceof WrappingType) {
                if (method_exists($graphqlType, 'getInnermostType')) {
                    $graphqlWrappedType = $graphqlType->getInnermostType();
                } else {
                    $graphqlWrappedType = $graphqlType->getWrappedType(true);
                }
            }
            $isStandardGraphqlType = \in_array($graphqlWrappedType, GraphQLType::getStandardTypes(), true);
            if ($isStandardGraphqlType) {
                $resourceClass = '';
            }

            // Check mercure attribute if it's a subscription at the root level.
            if ($rootOperation instanceof Subscription && null === $property && !$rootOperation->getMercure()) {
                return null;
            }

            $args = [];

            if (!$input && !$rootOperation instanceof Mutation && !$rootOperation instanceof Subscription && !$isStandardGraphqlType && $isCollectionType) {
                if (!$this->isEnumClass($resourceClass) && $this->pagination->isGraphQlEnabled($resourceOperation)) {
                    $args = $this->getGraphQlPaginationArgs($resourceOperation);
                }

                $args = $this->getFilterArgs($args, $resourceClass, $rootResource, $resourceOperation, $rootOperation, $property, $depth);
            }

            if ($this->itemResolverFactory instanceof ResolverFactory) {
                if ($isStandardGraphqlType || $input) {
                    $resolve = null;
                } else {
                    $resolve = ($this->itemResolverFactory)($resourceClass, $rootResource, $resourceOperation, $this->propertyMetadataFactory);
                }
            } else {
                if ($isStandardGraphqlType || $input) {
                    $resolve = null;
                } elseif (($rootOperation instanceof Mutation || $rootOperation instanceof Subscription) && $depth <= 0) {
                    $resolve = $rootOperation instanceof Mutation ? ($this->itemMutationResolverFactory)($resourceClass, $rootResource, $resourceOperation) : ($this->itemSubscriptionResolverFactory)($resourceClass, $rootResource, $resourceOperation);
                } elseif ($this->typeBuilder->isCollection($type)) {
                    $resolve = ($this->collectionResolverFactory)($resourceClass, $rootResource, $resourceOperation);
                } else {
                    $resolve = ($this->itemResolverFactory)($resourceClass, $rootResource, $resourceOperation);
                }
            }

            return [
                'type' => $graphqlType,
                'description' => $fieldDescription,
                'args' => $args,
                'resolve' => $resolve,
                'deprecationReason' => $deprecationReason,
            ];
        } catch (InvalidTypeException) {
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

    private function getFilterArgs(array $args, ?string $resourceClass, string $rootResource, Operation $resourceOperation, Operation $rootOperation, ?string $property, int $depth): array
    {
        if (null === $resourceClass) {
            return $args;
        }

        foreach ($resourceOperation->getFilters() ?? [] as $filterId) {
            if (!$this->filterLocator->has($filterId)) {
                continue;
            }

            $entityClass = $resourceClass;
            if ($options = $resourceOperation->getStateOptions()) {
                if ($options instanceof Options && $options->getEntityClass()) {
                    $entityClass = $options->getEntityClass();
                }

                if ($options instanceof ODMOptions && $options->getDocumentClass()) {
                    $entityClass = $options->getDocumentClass();
                }
            }

            foreach ($this->filterLocator->get($filterId)->getDescription($entityClass) as $key => $description) {
                $nullable = isset($description['required']) ? !$description['required'] : true;
                $filterType = \in_array($description['type'], Type::$builtinTypes, true) ? new Type($description['type'], $nullable) : new Type('object', $nullable, $description['type']);
                $graphqlFilterType = $this->convertType($filterType, false, $resourceOperation, $rootOperation, $resourceClass, $rootResource, $property, $depth);

                if (str_ends_with($key, '[]')) {
                    $graphqlFilterType = GraphQLType::listOf($graphqlFilterType);
                    $key = substr($key, 0, -2).'_list';
                }

                /** @var string $key */
                $key = str_replace('.', $this->nestingSeparator, $key);

                parse_str($key, $parsed);
                if (\array_key_exists($key, $parsed) && \is_array($parsed[$key])) {
                    $parsed = [$key => ''];
                }
                array_walk_recursive($parsed, static function (&$v) use ($graphqlFilterType): void {
                    $v = $graphqlFilterType;
                });
                $args = $this->mergeFilterArgs($args, $parsed, $resourceOperation, $key);
            }
        }

        return $this->convertFilterArgsToTypes($args);
    }

    private function mergeFilterArgs(array $args, array $parsed, ?Operation $operation = null, string $original = ''): array
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
    private function convertType(Type $type, bool $input, Operation $resourceOperation, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth, bool $forceNullable = false): GraphQLType|ListOfType|NonNull
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
            if (!$input && !$this->isEnumClass($resourceClass) && $this->pagination->isGraphQlEnabled($resourceOperation)) {
                // Deprecated path, to remove in API Platform 4.
                if ($this->typeBuilder instanceof TypeBuilderInterface) {
                    return $this->typeBuilder->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $resourceOperation);
                }

                return $this->typeBuilder->getPaginatedCollectionType($graphqlType, $resourceOperation);
            }

            return GraphQLType::listOf($graphqlType);
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
