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

use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Interface implemented to build a GraphQL type.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface TypeBuilderInterface
{
    /**
     * Gets the object type of the given resource.
     *
     * @return ObjectType|NonNull the object type, possibly wrapped by NonNull
     */
    public function getResourceObjectType(?string $resourceClass, ResourceMetadataCollection $resourceMetadataCollection, Operation $operation, bool $input, bool $wrapped = false, int $depth = 0): GraphQLType;

    /**
     * Get the interface type of a node.
     */
    public function getNodeInterface(): InterfaceType;

    /**
     * Gets the type of a paginated collection of the given resource type.
     */
    public function getResourcePaginatedCollectionType(GraphQLType $resourceType, string $resourceClass, Operation $operation): GraphQLType;

    /**
     * Returns true if a type is a collection.
     */
    public function isCollection(Type $type): bool;
}
