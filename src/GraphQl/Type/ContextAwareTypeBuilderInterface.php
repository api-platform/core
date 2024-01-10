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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Interface implemented to build a GraphQL type.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface ContextAwareTypeBuilderInterface
{
    /**
     * Gets the object type of the given resource.
     *
     * @param array<string, mixed>&array{input?: bool, wrapped?: bool, depth?: int} $context
     *
     * @return GraphQLType the object type, possibly wrapped by NonNull
     */
    public function getResourceObjectType(ResourceMetadataCollection $resourceMetadataCollection, Operation $operation, ?ApiProperty $propertyMetadata = null, array $context = []): GraphQLType;

    /**
     * Get the interface type of a node.
     */
    public function getNodeInterface(): InterfaceType;

    /**
     * Gets the type of a paginated collection of the given resource type.
     */
    public function getPaginatedCollectionType(GraphQLType $resourceType, Operation $operation): GraphQLType;

    /**
     * Gets the type corresponding to an enum.
     */
    public function getEnumType(Operation $operation): GraphQLType;

    /**
     * Returns true if a type is a collection.
     */
    public function isCollection(Type $type): bool;
}
