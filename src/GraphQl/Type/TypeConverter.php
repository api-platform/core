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

use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Converts a type to its GraphQL equivalent.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypeConverter implements TypeConverterInterface
{
    private $typeBuilder;
    private $typesContainer;
    private $resourceMetadataCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(TypeBuilderInterface $typeBuilder, TypesContainerInterface $typesContainer, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory = null)
    {
        $this->typeBuilder = $typeBuilder;
        $this->typesContainer = $typesContainer;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;

        if (null === $this->propertyMetadataFactory) {
            @trigger_error(sprintf('Not injecting %s in the TypeConverter is deprecated since 2.7 and will not be supported in 3.0.', PropertyMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertType(Type $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth)
    {
        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_BOOL:
                return GraphQLType::boolean();
            case Type::BUILTIN_TYPE_INT:
                return GraphQLType::int();
            case Type::BUILTIN_TYPE_FLOAT:
                return GraphQLType::float();
            case Type::BUILTIN_TYPE_STRING:
                return GraphQLType::string();
            case Type::BUILTIN_TYPE_ARRAY:
            case Type::BUILTIN_TYPE_ITERABLE:
                if ($resourceType = $this->getResourceType($type, $input, $rootOperation, $rootResource, $property, $depth)) {
                    return $resourceType;
                }

                return 'Iterable';
            case Type::BUILTIN_TYPE_OBJECT:
                if (is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                    return GraphQLType::string();
                }

                return $this->getResourceType($type, $input, $rootOperation, $rootResource, $property, $depth);
            default:
                return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveType(string $type): ?GraphQLType
    {
        try {
            $astTypeNode = Parser::parseType($type);
        } catch (SyntaxError $e) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid GraphQL type.', $type), 0, $e);
        }

        if ($graphQlType = $this->resolveAstTypeNode($astTypeNode, $type)) {
            return $graphQlType;
        }

        throw new InvalidArgumentException(sprintf('The type "%s" was not resolved.', $type));
    }

    private function getResourceType(Type $type, bool $input, Operation $rootOperation, string $rootResource, ?string $property, int $depth): ?GraphQLType
    {
        if (
            $this->typeBuilder->isCollection($type) &&
            $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()
        ) {
            $resourceClass = $collectionValueType->getClassName();
        } else {
            $resourceClass = $type->getClassName();
        }

        if (null === $resourceClass) {
            return null;
        }

        try {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $e) {
            return null;
        }

        $hasGraphQl = false;
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            if (null !== $resourceMetadata->getGraphQlOperations()) {
                $hasGraphQl = true;
                break;
            }
        }

        if (isset($resourceMetadataCollection[0]) && 'Node' === $resourceMetadataCollection[0]->getShortName()) {
            throw new \UnexpectedValueException('A "Node" resource cannot be used with GraphQL because the type is already used by the Relay specification.');
        }

        if (!$hasGraphQl) {
            return null;
        }

        $propertyMetadata = null;
        if ($property && $this->propertyMetadataFactory) {
            $context = [
                'normalization_groups' => $rootOperation->getNormalizationContext()['groups'] ?? null,
                'denormalization_groups' => $rootOperation->getDenormalizationContext()['groups'] ?? null,
            ];
            $propertyMetadata = $this->propertyMetadataFactory->create($rootResource, $property, $context);
        }

        if ($input && $depth > 0 && (!$propertyMetadata || !$propertyMetadata->isWritableLink())) {
            return GraphQLType::string();
        }

        $operationName = $rootOperation->getName();
        $isCollection = $rootOperation instanceof CollectionOperationInterface || 'collection_query' === $operationName;

        // We're retrieving the type of a property which is a relation to the rootResource
        if ($resourceClass !== $rootResource && $property && $rootOperation instanceof Query) {
            $isCollection = $this->typeBuilder->isCollection($type);
            $operationName = $isCollection ? 'collection_query' : 'item_query';
        }

        try {
            $operation = $resourceMetadataCollection->getOperation($operationName);

            if (!$operation instanceof Operation) {
                throw new OperationNotFoundException();
            }
        } catch (OperationNotFoundException $e) {
            /** @var Operation */
            $operation = ($isCollection ? new QueryCollection() : new Query())
                ->withResource($resourceMetadataCollection[0])
                ->withName($operationName);
        }

        return $this->typeBuilder->getResourceObjectType($resourceClass, $resourceMetadataCollection, $operation, $input, false, $depth);
    }

    private function resolveAstTypeNode(TypeNode $astTypeNode, string $fromType): ?GraphQLType
    {
        if ($astTypeNode instanceof NonNullTypeNode) {
            /** @var NullableType|null $nullableAstTypeNode */
            $nullableAstTypeNode = $this->resolveNullableAstTypeNode($astTypeNode->type, $fromType);

            return $nullableAstTypeNode ? GraphQLType::nonNull($nullableAstTypeNode) : null;
        }

        return $this->resolveNullableAstTypeNode($astTypeNode, $fromType);
    }

    private function resolveNullableAstTypeNode(TypeNode $astTypeNode, string $fromType): ?GraphQLType
    {
        if ($astTypeNode instanceof ListTypeNode) {
            /** @var TypeNode $astTypeNodeElement */
            $astTypeNodeElement = $astTypeNode->type;

            return GraphQLType::listOf($this->resolveAstTypeNode($astTypeNodeElement, $fromType));
        }

        if (!$astTypeNode instanceof NamedTypeNode) {
            return null;
        }

        $typeName = $astTypeNode->name->value;

        switch ($typeName) {
            case GraphQLType::STRING:
                return GraphQLType::string();
            case GraphQLType::INT:
                return GraphQLType::int();
            case GraphQLType::BOOLEAN:
                return GraphQLType::boolean();
            case GraphQLType::FLOAT:
                return GraphQLType::float();
            case GraphQLType::ID:
                return GraphQLType::id();
            default:
                if ($this->typesContainer->has($typeName)) {
                    return $this->typesContainer->get($typeName);
                }

                return null;
        }
    }
}
