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

use ApiPlatform\GraphQl\Exception\InvalidTypeException;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\SortFilterInterface;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\Metadata\Util\PropertyInfoToTypeInfoHelper;
use ApiPlatform\Metadata\Util\TypeHelper;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Util\StateOptionsTrait;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\WrappingType;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Builds the GraphQL fields.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class FieldsBuilder implements FieldsBuilderEnumInterface
{
    use StateOptionsTrait;

    private readonly ContextAwareTypeBuilderInterface $typeBuilder;

    public function __construct(private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ResourceClassResolverInterface $resourceClassResolver, private readonly TypesContainerInterface $typesContainer, ContextAwareTypeBuilderInterface $typeBuilder, private readonly TypeConverterInterface $typeConverter, private readonly ResolverFactoryInterface $resolverFactory, private readonly ContainerInterface $filterLocator, private readonly Pagination $pagination, private readonly ?NameConverterInterface $nameConverter, private readonly string $nestingSeparator, private readonly ?InflectorInterface $inflector = new Inflector())
    {
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
            'resolve' => ($this->resolverFactory)(),
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

        $fieldName = lcfirst('item_query' === $operation->getName() ? ($operation->getShortName() ?? $operation->getName()) : $operation->getName().$operation->getShortName());

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $operation->getDescription(), $operation->getDeprecationReason(), Type::nullable(Type::object($resourceClass)), $resourceClass, false, $operation)) {
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

        if ($fieldConfiguration = $this->getResourceFieldConfiguration(null, $operation->getDescription(), $operation->getDeprecationReason(), Type::collection(Type::object(\stdClass::class), Type::object($resourceClass)), $resourceClass, false, $operation)) {
            $args = $this->resolveResourceArgs($configuration['args'] ?? [], $operation);
            $extraArgs = $this->resolveResourceArgs($operation->getExtraArgs() ?? [], $operation);
            $configuration['args'] = $args ?: $configuration['args'] ?? $fieldConfiguration['args'] + $extraArgs;

            return [$this->inflector->pluralize($fieldName) => array_merge($fieldConfiguration, $configuration)];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMutationFields(string $resourceClass, Operation $operation): array
    {
        $mutationFields = [];
        $resourceType = Type::nullable(Type::object($resourceClass));
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
        $resourceType = Type::nullable(Type::object($resourceClass));
        $description = $operation->getDescription() ?? \sprintf('Subscribes to the action event of a %s.', $operation->getShortName());

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

                if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
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
                } else {
                    if (
                        !($propertyType = $propertyMetadata->getNativeType())
                        || (!$input && false === $propertyMetadata->isReadable())
                        || ($input && false === $propertyMetadata->isWritable())
                    ) {
                        continue;
                    }

                    if ($fieldConfiguration = $this->getResourceFieldConfiguration($property, $propertyMetadata->getDescription(), $propertyMetadata->getDeprecationReason(), $propertyType, $resourceClass, $input, $operation, $depth, null !== $propertyMetadata->getSecurity())) {
                        $fields['id' === $property ? '_id' : $this->normalizePropertyName($property, $resourceClass)] = $fieldConfiguration;
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
                throw new \InvalidArgumentException(\sprintf('The argument "%s" of the custom operation "%s" in %s needs a "type" option.', $id, $operation->getName(), $operation->getShortName()));
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
    private function getResourceFieldConfiguration(?string $property, ?string $fieldDescription, ?string $deprecationReason, Type|LegacyType $type, string $rootResource, bool $input, Operation $rootOperation, int $depth = 0, bool $forceNullable = false): ?array
    {
        if ($type instanceof LegacyType) {
            $type = PropertyInfoToTypeInfoHelper::convertLegacyTypesToType([$type]);
        }

        try {
            $isCollectionType = $type->isSatisfiedBy(static fn ($t) => $t instanceof CollectionType) && ($v = TypeHelper::getCollectionValueType($type)) && TypeHelper::getClassName($v);

            $valueType = $type;
            if ($isCollectionType) {
                $valueType = TypeHelper::getCollectionValueType($type);
            }

            /** @var class-string|null $resourceClass */
            $resourceClass = null;
            $typeIsResourceClass = function (Type $type) use (&$resourceClass): bool {
                return $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($resourceClass = $type->getClassName());
            };

            $isResourceClass = $valueType->isSatisfiedBy($typeIsResourceClass);

            $resourceOperation = $rootOperation;
            if ($resourceClass && $depth >= 1 && $isResourceClass) {
                $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
                $resourceOperation = $resourceMetadataCollection->getOperation($isCollectionType ? 'collection_query' : 'item_query');
            }

            if (!$resourceOperation instanceof Operation) {
                throw new \LogicException('The resource operation should be a GraphQL operation.');
            }

            $graphqlType = $this->convertType($type, $input, $resourceOperation, $rootOperation, $resourceClass ?? '', $rootResource, $property, $depth, $forceNullable);

            $graphqlWrappedType = $graphqlType;
            if ($graphqlType instanceof WrappingType) {
                $graphqlWrappedType = $graphqlType->getInnermostType();
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

            if (!$input && !$rootOperation instanceof Mutation && !$rootOperation instanceof Subscription && !$isStandardGraphqlType) {
                if ($isCollectionType) {
                    if (!$this->isEnumClass($resourceClass) && $this->pagination->isGraphQlEnabled($resourceOperation)) {
                        $args = $this->getGraphQlPaginationArgs($resourceOperation);
                    }

                    $args = $this->getCollectionFilterArgs($args, $resourceClass, $rootResource, $resourceOperation, $rootOperation, $property, $depth);
                }
            }

            if ($isStandardGraphqlType || $input) {
                $resolve = null;
            } else {
                $resolve = ($this->resolverFactory)($resourceClass, $rootResource, $resourceOperation, $this->propertyMetadataFactory);
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

    /**
     * Single entry point for GraphQL collection-field arguments.
     *
     * Builds one intermediate "arg tree" from BOTH the legacy `Operation::getFilters()`
     * descriptions and the canonical `Operation::getParameters()` (#[QueryParameter]),
     * then materializes it into GraphQL types once via {@see argTreeToGraphQLType()}.
     *
     * It is called with the *resource* operation (not the root one): for a nested
     * relation field, $resourceOperation is the related resource's collection_query,
     * so its own parameters/filters surface as nested arguments on that sub-field.
     */
    private function getCollectionFilterArgs(array $args, ?string $resourceClass, string $rootResource, Operation $resourceOperation, Operation $rootOperation, ?string $property, int $depth): array
    {
        if (null === $resourceClass) {
            return $args;
        }

        $tree = [];
        $this->buildFilterArgTree($tree, $resourceClass, $rootResource, $resourceOperation, $rootOperation, $property, $depth);
        $this->buildParameterArgTree($tree, $resourceOperation);

        return $args + $this->argTreeToGraphQLType($tree);
    }

    /**
     * Feeds the arg tree from the legacy `Operation::getFilters()` descriptions.
     *
     * A leaf is a GraphQLType; a nested node is an array carrying a reserved `#name`
     * (the generated InputObjectType name). Nested filter nodes are list-wrapped to
     * preserve the historical GraphQL filter shape (e.g. `order: [..]`, `availableAt: [..]`).
     *
     * @param array<string, mixed> $tree
     */
    private function buildFilterArgTree(array &$tree, string $resourceClass, string $rootResource, Operation $resourceOperation, Operation $rootOperation, ?string $property, int $depth): void
    {
        foreach ($resourceOperation->getFilters() ?? [] as $filterId) {
            if (!($filter = $this->resolveFilter($filterId))) {
                continue;
            }

            $entityClass = $this->getStateOptionsClass($resourceOperation, $resourceOperation->getClass());
            foreach ($filter->getDescription($entityClass) as $key => $description) {
                $filterType = \in_array($description['type'], TypeIdentifier::values(), true) ? Type::builtin($description['type']) : Type::object($description['type']);
                if (!($description['required'] ?? false)) {
                    $filterType = Type::nullable($filterType);
                }
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
                $this->mergeArgTree($tree, $parsed, $resourceOperation->getShortName(), $key);
            }
        }
    }

    /**
     * Feeds the arg tree from the canonical `Operation::getParameters()`.
     *
     * Each parameter's shape is derived from its JSON Schema (via
     * {@see JsonSchemaFilterInterface::getSchema()}, e.g. ComparisonFilter exposing
     * gt/gte/lt/lte/ne) and falls back to its `getNativeType()` for plain scalars.
     * Bracketed keys (`order[:property]` → `order[name]`) collapse into a single
     * list-wrapped input object; dotted keys (`colors.price`) flatten to a nested
     * key (`colors__price`) so the runtime `__`→`.` contract is preserved.
     *
     * @param array<string, mixed> $tree
     */
    private function buildParameterArgTree(array &$tree, Operation $operation): void
    {
        foreach ($operation->getParameters() ?? [] as $parameter) {
            $key = $parameter->getKey();
            if (null === $key) {
                continue;
            }

            $filter = $this->resolveFilter($parameter->getFilter());
            $schema = ($filter instanceof JsonSchemaFilterInterface ? $filter->getSchema($parameter) : null) ?? $parameter->getSchema();
            $leafType = $this->parameterLeafType($parameter, $schema);

            if (str_contains($key, '[')) {
                // Bracketed key (order[name], order[:property] expanded). The portion
                // before the first bracket becomes one input object whose fields are
                // the bracketed accessors, list-shaped for :property-template filters.
                $rootKey = substr($key, 0, (int) strpos($key, '['));
                preg_match_all('/\[([^\[\]]+)\]/', $key, $matches);
                $accessors = $matches[1];

                $name = $rootKey.$operation->getShortName().$operation->getName();
                $node = $tree[$rootKey] ?? ['#name' => $name, '#list' => $this->isListParameter($filter)];
                if (!\is_array($node)) {
                    // A scalar leaf (written by a filter) already holds this key; a
                    // bracketed parameter cannot merge into a non-object argument.
                    continue;
                }
                $entityClass = $this->getStateOptionsClass($operation, $operation->getClass() ?? '');
                $cursor = &$node;
                foreach ($accessors as $i => $accessor) {
                    if ($i === \count($accessors) - 1) {
                        if (!isset($cursor[$accessor])) {
                            $cursor[$accessor] = $this->bracketLeaf($parameter, $filter, $accessor, $leafType, $name, $entityClass);
                        }
                        break;
                    }
                    $cursor[$accessor] ??= ['#name' => $name.'_'.$accessor, '#list' => false];
                    $cursor = &$cursor[$accessor];
                }
                unset($cursor);
                $tree[$rootKey] = $node;
                continue;
            }

            // Dotted key: flatten to the nesting-separator form (colors.price -> colors__price)
            // so it matches the legacy runtime contract (ReadProvider converts __ back to .).
            $argKey = str_replace('.', $this->nestingSeparator, $key);

            if (\is_array($schema) && 'object' === ($schema['type'] ?? null) && \is_array($schema['properties'] ?? null)) {
                // Operator form (e.g. ComparisonFilter gt/gte/lt/lte/ne): a non-list input object.
                $name = $operation->getShortName().$operation->getName().'_'.strtr($argKey, ['.' => '__']);
                $node = ['#name' => $name, '#list' => false, '#nonNull' => (bool) $parameter->getRequired()];
                foreach ($schema['properties'] as $prop => $propSchema) {
                    $propSchema = \is_array($propSchema) ? $propSchema : [];
                    // The operator's inner schema is often a bare {type:string} placeholder
                    // (ComparisonFilter wraps an untyped equality filter); prefer the
                    // parameter's native type so an int property yields GraphQL Int.
                    $node[$prop] = 'string' === ($propSchema['type'] ?? 'string') ? $leafType : $this->jsonSchemaToGraphQLType($propSchema);
                }
                $tree[$argKey] = $node;
                continue;
            }

            $type = $leafType;
            if ($parameter->getRequired()) {
                $type = GraphQLType::nonNull($type);
            }
            $tree[$argKey] = $type;
        }
    }

    /**
     * Whether a bracketed parameter exposes a list-shaped GraphQL argument
     * (e.g. `order: [{name: "DESC"}, {description: "ASC"}]`) instead of a single
     * input object.
     *
     * Only sort filters are sequence-sensitive: GraphQL input-object fields are
     * unordered, so multi-key ordering cannot be expressed as one object and must
     * be a list. Every other bracketed filter (search, comparison, date, exists)
     * is a single input object. Recognized through the backend-agnostic
     * {@see SortFilterInterface}, keeping this component free of any persistence
     * dependency.
     */
    private function isListParameter(?FilterInterface $filter): bool
    {
        return $filter instanceof SortFilterInterface;
    }

    /**
     * Computes the leaf for a bracketed-parameter accessor.
     *
     * A scalar by default, but enriched from the filter's `getDescription()`: when the
     * description for the accessor's property exposes sub-keys it becomes either a
     * `listOf` (sequential `foo[]` form) or a nested non-list input object (e.g. a date
     * filter's `createdAt[before]`/`[after]`), preserving the historical shape.
     *
     * @return GraphQLType|array<string, mixed>
     */
    private function bracketLeaf(Parameter $parameter, ?FilterInterface $filter, string $accessor, GraphQLType $leafType, string $parentName, string $entityClass): GraphQLType|array
    {
        if (!$filter instanceof FilterInterface) {
            return $leafType;
        }

        $property = $parameter->getProperty() ?? $accessor;
        $property = str_replace('.', $this->nestingSeparator, $property);

        $descriptionLeafs = [];
        foreach ($filter->getDescription($entityClass) as $descKey => $descValue) {
            $descKey = str_replace('.', $this->nestingSeparator, $descKey);
            parse_str($descKey, $descValues);
            if (isset($descValues[$property]) && \is_array($descValues[$property])) {
                $descriptionLeafs = array_merge($descriptionLeafs, $descValues[$property]);
            }
        }

        if (!$descriptionLeafs) {
            return $leafType;
        }

        // Sequential array (e.g. foo[]) => list of the scalar leaf.
        if (0 === key($descriptionLeafs)) {
            return GraphQLType::listOf($leafType);
        }

        // Associative sub-keys (e.g. before/after) => nested non-list input object.
        $node = ['#name' => $parentName.'_'.$accessor, '#list' => false];
        foreach (array_keys($descriptionLeafs) as $subKey) {
            $node[$subKey] = GraphQLType::string();
        }

        return $node;
    }

    /**
     * Merges a parsed legacy-filter subtree into the shared arg tree, tagging nested
     * nodes with the generated `#name` used for InputObjectType dedup.
     *
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $parsed
     */
    private function mergeArgTree(array &$tree, array $parsed, string $shortName, string $original): void
    {
        foreach ($parsed as $key => $value) {
            // Never override keys that cannot be merged.
            if (isset($tree[$key]) && !\is_array($tree[$key])) {
                continue;
            }

            if (\is_array($value)) {
                $sub = $tree[$key] ?? [];
                $this->mergeArgTree($sub, $value, $shortName, $original);
                if (!isset($sub['#name'])) {
                    $name = (false === $pos = strrpos($original, '[')) ? $original : substr($original, 0, (int) $pos);
                    $sub['#name'] = $shortName.'Filter_'.strtr($name, ['[' => '_', ']' => '', '.' => '__']);
                    $sub['#list'] = true;
                }
                $tree[$key] = $sub;
                continue;
            }

            $tree[$key] = $value;
        }
    }

    /**
     * Materializes an arg tree into GraphQL argument definitions.
     *
     * Leaves are GraphQLType instances. A nested node (array) is converted to an
     * `InputObjectType` named by its `#name` marker, list-wrapped when `#list` is
     * true. Generated input objects are registered in the TypesContainer and reused
     * on name collision (dedup).
     *
     * @param array<string, mixed> $tree
     *
     * @return array<string, mixed>
     */
    private function argTreeToGraphQLType(array $tree): array
    {
        $args = [];
        foreach ($tree as $key => $value) {
            if ($value instanceof GraphQLType) {
                $args[$key] = $value;
                continue;
            }

            if (\is_array($value) && isset($value['#name'])) {
                $args[$key] = $this->buildInputObjectType($value);
            }
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function buildInputObjectType(array $node): GraphQLType
    {
        $name = $node['#name'];
        $list = $node['#list'] ?? true;
        $nonNull = $node['#nonNull'] ?? false;

        if ($this->typesContainer->has($name)) {
            return $this->typesContainer->get($name);
        }

        unset($node['#name'], $node['#list'], $node['#nonNull']);

        $fields = [];
        foreach ($node as $fieldKey => $fieldValue) {
            if ($fieldValue instanceof GraphQLType) {
                $fields[$fieldKey] = $fieldValue;
                continue;
            }

            if (\is_array($fieldValue) && isset($fieldValue['#name'])) {
                $fields[$fieldKey] = $this->buildInputObjectType($fieldValue);
            }
        }

        $inputObject = new InputObjectType(['name' => $name, 'fields' => $fields]);
        $type = $list ? GraphQLType::listOf($inputObject) : $inputObject;
        if ($nonNull) {
            $type = GraphQLType::nonNull($type);
        }

        $this->typesContainer->set($name, $type);

        return $type;
    }

    /**
     * Resolves the scalar GraphQL leaf type for a parameter, from its JSON Schema
     * scalar type when available, otherwise from its native (PHP) type.
     *
     * @param array<string, mixed>|null $schema
     */
    private function parameterLeafType(Parameter $parameter, ?array $schema): GraphQLType
    {
        if (\is_array($schema) && isset($schema['type']) && \is_string($schema['type']) && 'object' !== $schema['type'] && 'array' !== $schema['type']) {
            return $this->jsonSchemaToGraphQLType($schema);
        }

        if ($nativeType = $parameter->getNativeType()) {
            return $this->nativeTypeToGraphQLType($nativeType);
        }

        return GraphQLType::string();
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function jsonSchemaToGraphQLType(array $schema): GraphQLType
    {
        if ('array' === ($schema['type'] ?? null)) {
            $items = \is_array($schema['items'] ?? null) ? $schema['items'] : ['type' => 'string'];

            return GraphQLType::listOf($this->jsonSchemaToGraphQLType($items));
        }

        return match ($schema['type'] ?? 'string') {
            'integer' => GraphQLType::int(),
            'number' => GraphQLType::float(),
            'boolean' => GraphQLType::boolean(),
            default => GraphQLType::string(),
        };
    }

    private function nativeTypeToGraphQLType(Type $type): GraphQLType
    {
        if ($type->isIdentifiedBy(TypeIdentifier::BOOL)) {
            return GraphQLType::boolean();
        }

        if ($type->isIdentifiedBy(TypeIdentifier::INT)) {
            return GraphQLType::int();
        }

        if ($type->isIdentifiedBy(TypeIdentifier::FLOAT)) {
            return GraphQLType::float();
        }

        if ($type->isIdentifiedBy(TypeIdentifier::STRING, TypeIdentifier::OBJECT)) {
            return GraphQLType::string();
        }

        if ($type instanceof CollectionType) {
            return GraphQLType::listOf($this->nativeTypeToGraphQLType($type->getCollectionValueType()));
        }

        return GraphQLType::string();
    }

    /**
     * Converts a built-in type to its GraphQL equivalent.
     *
     * @throws InvalidTypeException
     */
    private function convertType(Type|LegacyType $type, bool $input, Operation $resourceOperation, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth, bool $forceNullable = false): GraphQLType|ListOfType|NonNull
    {
        if ($type instanceof LegacyType) {
            $type = PropertyInfoToTypeInfoHelper::convertLegacyTypesToType([$type]);
        }

        $graphqlType = $this->typeConverter->convertPhpType($type, $input, $rootOperation, $resourceClass, $rootResource, $property, $depth);

        if (null === $graphqlType) {
            throw new InvalidTypeException(\sprintf('The type "%s" is not supported.', (string) $type));
        }

        if (\is_string($graphqlType)) {
            if (!$this->typesContainer->has($graphqlType)) {
                throw new InvalidTypeException(\sprintf('The GraphQL type %s is not valid. Valid types are: %s. Have you registered this type by implementing %s?', $graphqlType, implode(', ', array_keys($this->typesContainer->all())), TypeInterface::class));
            }

            $graphqlType = $this->typesContainer->get($graphqlType);
        }

        if ($type->isSatisfiedBy(static fn ($t) => $t instanceof CollectionType) && ($collectionValueType = TypeHelper::getCollectionValueType($type)) && TypeHelper::getClassName($collectionValueType)) {
            if (!$input && !$this->isEnumClass($resourceClass) && $this->pagination->isGraphQlEnabled($resourceOperation)) {
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

        return $this->nameConverter->normalize($property, $resourceClass);
    }

    /**
     * Resolves a filter reference to a {@see FilterInterface} instance, supporting
     * both a string service id (legacy/locator path) and an object form
     * (`new QueryParameter(filter: new SortFilter())`).
     */
    private function resolveFilter(mixed $filter): ?FilterInterface
    {
        if ($filter instanceof FilterInterface) {
            return $filter;
        }

        if (\is_string($filter) && $this->filterLocator->has($filter)) {
            $resolved = $this->filterLocator->get($filter);

            return $resolved instanceof FilterInterface ? $resolved : null;
        }

        return null;
    }
}
