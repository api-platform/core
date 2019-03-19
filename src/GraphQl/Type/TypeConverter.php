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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Convert a built-in type to its GraphQL equivalent.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypeConverter implements TypeConverterInterface
{
    private $resourceMetadataFactory;
    private $typeBuilder;

    public function __construct(TypeBuilderInterface $typeBuilder, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->typeBuilder = $typeBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function convertType(Type $type, bool $input, ?string $queryName, ?string $mutationName, string $resourceClass, ?string $property, int $depth)
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
                return 'Iterable';
            case Type::BUILTIN_TYPE_OBJECT:
                if ($input && $depth > 0) {
                    return GraphQLType::string();
                }

                if (is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                    return GraphQLType::string();
                }

                return $this->getResourceType($type, $input, $queryName, $mutationName, $depth);
            default:
                return null;
        }
    }

    private function getResourceType(Type $type, bool $input, ?string $queryName, ?string $mutationName, int $depth): ?GraphQLType
    {
        $resourceClass = $this->typeBuilder->isCollection($type) && ($collectionValueType = $type->getCollectionValueType()) ? $collectionValueType->getClassName() : $type->getClassName();
        if (null === $resourceClass) {
            return null;
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if ([] === ($resourceMetadata->getGraphql() ?? [])) {
                return null;
            }
        } catch (ResourceClassNotFoundException $e) {
            // Skip objects that are not resources for now
            return null;
        }

        return $this->typeBuilder->getResourceObjectType($resourceClass, $resourceMetadata, $input, $queryName, $mutationName, false, $depth);
    }
}
