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

namespace ApiPlatform\Core\GraphQl\Type;

use ApiPlatform\Core\GraphQl\Type\TypesContainerInterface as TypesContainerLegacyInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
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
    /** @var TypesContainerLegacyInterface|TypesContainerInterface */
    private $typesContainer;
    private $resourceMetadataFactory;
    /** @var LegacyPropertyMetadataFactoryInterface|PropertyMetadataFactoryInterface|null */
    private $propertyMetadataFactory;

    public function __construct(TypeBuilderInterface $typeBuilder, $typesContainer, ResourceMetadataFactoryInterface $resourceMetadataFactory, $propertyMetadataFactory = null)
    {
        $this->typeBuilder = $typeBuilder;
        $this->typesContainer = $typesContainer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;

        if (null === $this->propertyMetadataFactory) {
            @trigger_error(sprintf('Not injecting %s in the TypeConverter is deprecated since 2.7 and will not be supported in 3.0.', PropertyMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertType(Type $type, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, string $resourceClass, string $rootResource, ?string $property, int $depth)
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
                if ($resourceType = $this->getResourceType($type, $input, $queryName, $mutationName, $subscriptionName, $rootResource, $property, $depth)) {
                    return $resourceType;
                }

                return 'Iterable';
            case Type::BUILTIN_TYPE_OBJECT:
                if (is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                    return GraphQLType::string();
                }

                return $this->getResourceType($type, $input, $queryName, $mutationName, $subscriptionName, $rootResource, $property, $depth);
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

    private function getResourceType(Type $type, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, string $rootResource, ?string $property, int $depth): ?GraphQLType
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
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if (null === $resourceMetadata->getGraphql()) {
                return null;
            }
            if ('Node' === $resourceMetadata->getShortName()) {
                throw new \UnexpectedValueException('A "Node" resource cannot be used with GraphQL because the type is already used by the Relay specification.');
            }
        } catch (ResourceClassNotFoundException $e) {
            // Skip objects that are not resources for now
            return null;
        }

        $propertyMetadata = null;
        if ($property && $this->propertyMetadataFactory) {
            $rootResourceMetadata = null;
            try {
                $rootResourceMetadata = $this->resourceMetadataFactory->create($rootResource);
            } catch (ResourceClassNotFoundException $e) {
            }

            $context = [
                'normalization_groups' => $rootResourceMetadata ? $rootResourceMetadata->getGraphqlAttribute($queryName ?? $mutationName ?? $subscriptionName, 'normalization_context', [], true)['groups'] ?? null : null,
                'denormalization_groups' => $rootResourceMetadata ? $rootResourceMetadata->getGraphqlAttribute($queryName ?? $mutationName ?? $subscriptionName, 'denormalization_context', [], true)['groups'] ?? null : null,
            ];
            $propertyMetadata = $this->propertyMetadataFactory->create($rootResource, $property, $context);
        }

        if ($input && $depth > 0 && (!$propertyMetadata || !$propertyMetadata->isWritableLink())) {
            return GraphQLType::string();
        }

        return $this->typeBuilder->getResourceObjectType($resourceClass, $resourceMetadata, $input, $queryName, $mutationName, $subscriptionName, false, $depth);
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
