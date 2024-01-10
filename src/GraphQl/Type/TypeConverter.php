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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
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
    public function __construct(private readonly ContextAwareTypeBuilderInterface|TypeBuilderEnumInterface|TypeBuilderInterface $typeBuilder, private readonly TypesContainerInterface $typesContainer, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        if ($typeBuilder instanceof TypeBuilderInterface) {
            @trigger_error(sprintf('$typeBuilder argument of TypeConverter implementing "%s" is deprecated since API Platform 3.1. It has to implement "%s" instead.', TypeBuilderInterface::class, TypeBuilderEnumInterface::class), \E_USER_DEPRECATED);
        }

        if ($typeBuilder instanceof TypeBuilderEnumInterface) {
            @trigger_error(sprintf('$typeBuilder argument of TypeConverter implementing "%s" is deprecated since API Platform 3.3. It has to implement "%s" instead.', TypeBuilderEnumInterface::class, ContextAwareTypeBuilderInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertType(Type $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth): GraphQLType|string|null
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
            $this->typeBuilder->isCollection($type)
            && $collectionValueType = $type->getCollectionValueTypes()[0] ?? null
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
        } catch (ResourceClassNotFoundException) {
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
            if (is_a($resourceClass, \BackedEnum::class, true)) {
                // Remove the condition in API Platform 4.
                if ($this->typeBuilder instanceof TypeBuilderEnumInterface || $this->typeBuilder instanceof ContextAwareTypeBuilderInterface) {
                    $operation = null;
                    try {
                        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
                        $operation = $resourceMetadataCollection->getOperation();
                    } catch (ResourceClassNotFoundException|OperationNotFoundException) {
                    }
                    /** @var Query $enumOperation */
                    $enumOperation = (new Query())
                        ->withClass($resourceClass)
                        ->withShortName($operation?->getShortName() ?? (new \ReflectionClass($resourceClass))->getShortName())
                        ->withDescription($operation?->getDescription());

                    return $this->typeBuilder->getEnumType($enumOperation);
                }
            }

            return null;
        }

        $propertyMetadata = null;
        if ($property) {
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
        $isCollection = $this->typeBuilder->isCollection($type);

        // We're retrieving the type of a property which is a relation to the root resource.
        if ($resourceClass !== $rootResource && $rootOperation instanceof Query) {
            $operationName = $isCollection ? 'collection_query' : 'item_query';
        }

        try {
            $operation = $resourceMetadataCollection->getOperation($operationName);
        } catch (OperationNotFoundException) {
            $operation = $resourceMetadataCollection->getOperation($isCollection ? 'collection_query' : 'item_query');
        }
        if (!$operation instanceof Operation) {
            throw new OperationNotFoundException();
        }

        return $this->typeBuilder instanceof ContextAwareTypeBuilderInterface ?
            $this->typeBuilder->getResourceObjectType($resourceMetadataCollection, $operation, $propertyMetadata, [
                'input' => $input,
                'wrapped' => false,
                'depth' => $depth,
            ]) :
            $this->typeBuilder->getResourceObjectType($resourceClass, $resourceMetadataCollection, $operation, $input, false, $depth);
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

        return match ($typeName) {
            GraphQLType::STRING => GraphQLType::string(),
            GraphQLType::INT => GraphQLType::int(),
            GraphQLType::BOOLEAN => GraphQLType::boolean(),
            GraphQLType::FLOAT => GraphQLType::float(),
            GraphQLType::ID => GraphQLType::id(),
            default => $this->typesContainer->has($typeName) ? $this->typesContainer->get($typeName) : null,
        };
    }
}
