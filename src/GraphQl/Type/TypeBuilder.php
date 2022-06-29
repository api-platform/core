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

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\Pagination;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Builds the GraphQL types.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypeBuilder implements TypeBuilderInterface
{
    private $typesContainer;
    private $defaultFieldResolver;
    private $fieldsBuilderLocator;
    private $pagination;

    public function __construct(TypesContainerInterface $typesContainer, callable $defaultFieldResolver, ContainerInterface $fieldsBuilderLocator, Pagination $pagination)
    {
        $this->typesContainer = $typesContainer;
        $this->defaultFieldResolver = $defaultFieldResolver;
        $this->fieldsBuilderLocator = $fieldsBuilderLocator;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceObjectType(?string $resourceClass, ResourceMetadataCollection $resourceMetadataCollection, Operation $operation, bool $input, bool $wrapped = false, int $depth = 0): GraphQLType
    {
        $shortName = $operation->getShortName();
        $operationName = $operation->getName();

        if ($operation instanceof Mutation) {
            $shortName = $operationName.ucfirst($shortName);
        }

        if ($operation instanceof Subscription) {
            $shortName = $operationName.ucfirst($shortName).'Subscription';
        }

        if ($input) {
            if ($depth > 0) {
                $shortName .= 'Nested';
            }
            $shortName .= 'Input';
        } elseif ($operation instanceof Mutation || $operation instanceof Subscription) {
            if ($depth > 0) {
                $shortName .= 'Nested';
            }
            $shortName .= 'Payload';
        }

        if ('item_query' === $operationName || 'collection_query' === $operationName) {
            // Test if the collection/item operation exists and it has different groups
            try {
                if ($resourceMetadataCollection->getOperation($operation instanceof CollectionOperationInterface ? 'item_query' : 'collection_query')->getNormalizationContext() !== $operation->getNormalizationContext()) {
                    $shortName .= $operation instanceof CollectionOperationInterface ? 'Collection' : 'Item';
                }
            } catch (OperationNotFoundException $e) {
            }
        }

        if ($wrapped && ($operation instanceof Mutation || $operation instanceof Subscription)) {
            $shortName .= 'Data';
        }

        if ($this->typesContainer->has($shortName)) {
            $resourceObjectType = $this->typesContainer->get($shortName);
            if (!($resourceObjectType instanceof ObjectType || $resourceObjectType instanceof NonNull)) {
                throw new \LogicException(sprintf('Expected GraphQL type "%s" to be %s.', $shortName, implode('|', [ObjectType::class, NonNull::class])));
            }

            return $resourceObjectType;
        }

        $ioMetadata = $input ? $operation->getInput() : $operation->getOutput();
        if (null !== $ioMetadata && \array_key_exists('class', $ioMetadata) && null !== $ioMetadata['class']) {
            $resourceClass = $ioMetadata['class'];
        }

        $wrapData = !$wrapped && ($operation instanceof Mutation || $operation instanceof Subscription) && !$input && $depth < 1;

        $configuration = [
            'name' => $shortName,
            'description' => $operation->getDescription(),
            'resolveField' => $this->defaultFieldResolver,
            'fields' => function () use ($resourceClass, $operation, $operationName, $resourceMetadataCollection, $input, $wrapData, $depth, $ioMetadata) {
                if ($wrapData) {
                    $queryOperation = $this->getQueryOperation($resourceMetadataCollection);
                    $queryNormalizationContext = $queryOperation ? ($queryOperation->getNormalizationContext() ?? []) : [];

                    try {
                        $mutationNormalizationContext = $operation instanceof Mutation || $operation instanceof Subscription ? ($resourceMetadataCollection->getOperation($operationName)->getNormalizationContext() ?? []) : [];
                    } catch (OperationNotFoundException $e) {
                        $mutationNormalizationContext = [];
                    }
                    // Use a new type for the wrapped object only if there is a specific normalization context for the mutation or the subscription.
                    // If not, use the query type in order to ensure the client cache could be used.
                    $useWrappedType = $queryNormalizationContext !== $mutationNormalizationContext;

                    $wrappedOperationName = $operationName;

                    if (!$useWrappedType) {
                        $wrappedOperationName = $operation instanceof Query ? $operationName : 'item_query';
                    }

                    try {
                        $wrappedOperation = $resourceMetadataCollection->getOperation($wrappedOperationName);
                    } catch (OperationNotFoundException $e) {
                        $wrappedOperation = ('collection_query' === $wrappedOperationName ? new QueryCollection() : new Query())
                            ->withResource($resourceMetadataCollection[0])
                            ->withName($wrappedOperationName);
                    }

                    $fields = [
                        lcfirst($wrappedOperation->getShortName()) => $this->getResourceObjectType($resourceClass, $resourceMetadataCollection, $wrappedOperation instanceof Operation ? $wrappedOperation : null, $input, true, $depth),
                    ];

                    if ($operation instanceof Subscription) {
                        $fields['clientSubscriptionId'] = GraphQLType::string();
                        if ($operation->getMercure()) {
                            $fields['mercureUrl'] = GraphQLType::string();
                        }

                        return $fields;
                    }

                    return $fields + ['clientMutationId' => GraphQLType::string()];
                }

                $fieldsBuilder = $this->fieldsBuilderLocator->get('api_platform.graphql.fields_builder');
                $fields = $fieldsBuilder->getResourceObjectTypeFields($resourceClass, $operation, $input, $depth, $ioMetadata);

                if ($input && $operation instanceof Mutation && null !== $mutationArgs = $operation->getArgs()) {
                    return $fieldsBuilder->resolveResourceArgs($mutationArgs, $operation) + ['clientMutationId' => $fields['clientMutationId']];
                }

                return $fields;
            },
            'interfaces' => $wrapData ? [] : [$this->getNodeInterface()],
        ];

        $resourceObjectType = $input ? GraphQLType::nonNull(new InputObjectType($configuration)) : new ObjectType($configuration);
        $this->typesContainer->set($shortName, $resourceObjectType);

        return $resourceObjectType;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeInterface(): InterfaceType
    {
        if ($this->typesContainer->has('Node')) {
            $nodeInterface = $this->typesContainer->get('Node');
            if (!$nodeInterface instanceof InterfaceType) {
                throw new \LogicException(sprintf('Expected GraphQL type "Node" to be %s.', InterfaceType::class));
            }

            return $nodeInterface;
        }

        $nodeInterface = new InterfaceType([
            'name' => 'Node',
            'description' => 'A node, according to the Relay specification.',
            'fields' => [
                'id' => [
                    'type' => GraphQLType::nonNull(GraphQLType::id()),
                    'description' => 'The id of this node.',
                ],
            ],
            'resolveType' => function ($value) {
                if (!isset($value[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
                    return null;
                }

                $shortName = (new \ReflectionClass($value[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY]))->getShortName();

                return $this->typesContainer->has($shortName) ? $this->typesContainer->get($shortName) : null;
            },
        ]);

        $this->typesContainer->set('Node', $nodeInterface);

        return $nodeInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourcePaginatedCollectionType(GraphQLType $resourceType, string $resourceClass, Operation $operation): GraphQLType
    {
        $shortName = $resourceType->name;
        $paginationType = $this->pagination->getGraphQlPaginationType($operation);

        $connectionTypeKey = sprintf('%s%sConnection', $shortName, ucfirst($paginationType));
        if ($this->typesContainer->has($connectionTypeKey)) {
            return $this->typesContainer->get($connectionTypeKey);
        }

        $fields = 'cursor' === $paginationType ?
            $this->getCursorBasedPaginationFields($resourceType) :
            $this->getPageBasedPaginationFields($resourceType);

        $configuration = [
            'name' => $connectionTypeKey,
            'description' => sprintf("%s connection for $shortName.", ucfirst($paginationType)),
            'fields' => $fields,
        ];

        $resourcePaginatedCollectionType = new ObjectType($configuration);
        $this->typesContainer->set($connectionTypeKey, $resourcePaginatedCollectionType);

        return $resourcePaginatedCollectionType;
    }

    /**
     * {@inheritdoc}
     */
    public function isCollection(Type $type): bool
    {
        return $type->isCollection() && ($collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()) && null !== $collectionValueType->getClassName();
    }

    private function getCursorBasedPaginationFields(GraphQLType $resourceType): array
    {
        $shortName = $resourceType->name;

        $edgeObjectTypeConfiguration = [
            'name' => "{$shortName}Edge",
            'description' => "Edge of $shortName.",
            'fields' => [
                'node' => $resourceType,
                'cursor' => GraphQLType::nonNull(GraphQLType::string()),
            ],
        ];
        $edgeObjectType = new ObjectType($edgeObjectTypeConfiguration);
        $this->typesContainer->set("{$shortName}Edge", $edgeObjectType);

        $pageInfoObjectTypeConfiguration = [
            'name' => "{$shortName}PageInfo",
            'description' => 'Information about the current page.',
            'fields' => [
                'endCursor' => GraphQLType::string(),
                'startCursor' => GraphQLType::string(),
                'hasNextPage' => GraphQLType::nonNull(GraphQLType::boolean()),
                'hasPreviousPage' => GraphQLType::nonNull(GraphQLType::boolean()),
            ],
        ];
        $pageInfoObjectType = new ObjectType($pageInfoObjectTypeConfiguration);
        $this->typesContainer->set("{$shortName}PageInfo", $pageInfoObjectType);

        return [
            'edges' => GraphQLType::listOf($edgeObjectType),
            'pageInfo' => GraphQLType::nonNull($pageInfoObjectType),
            'totalCount' => GraphQLType::nonNull(GraphQLType::int()),
        ];
    }

    private function getPageBasedPaginationFields(GraphQLType $resourceType): array
    {
        $shortName = $resourceType->name;

        $paginationInfoObjectTypeConfiguration = [
            'name' => "{$shortName}PaginationInfo",
            'description' => 'Information about the pagination.',
            'fields' => [
                'itemsPerPage' => GraphQLType::nonNull(GraphQLType::int()),
                'lastPage' => GraphQLType::nonNull(GraphQLType::int()),
                'totalCount' => GraphQLType::nonNull(GraphQLType::int()),
            ],
        ];
        $paginationInfoObjectType = new ObjectType($paginationInfoObjectTypeConfiguration);
        $this->typesContainer->set("{$shortName}PaginationInfo", $paginationInfoObjectType);

        return [
            'collection' => GraphQLType::listOf($resourceType),
            'paginationInfo' => GraphQLType::nonNull($paginationInfoObjectType),
        ];
    }

    private function getQueryOperation(ResourceMetadataCollection $resourceMetadataCollection): ?Operation
    {
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getGraphQlOperations() as $operation) {
                // Filter the custom queries.
                if ($operation instanceof Query && !$operation->getResolver()) {
                    return $operation;
                }
            }
        }

        return null;
    }
}
