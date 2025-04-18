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
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;

/**
 * Converts a type to its GraphQL equivalent.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @method GraphQLType|string|null convertPhpType(Type $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth)
 */
interface TypeConverterInterface
{
    /**
     * @deprecated since 4.1, use "convertPhpType" instead
     *
     * Converts a built-in type to its GraphQL equivalent.
     * A string can be returned for a custom registered type.
     */
    public function convertType(LegacyType $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth): GraphQLType|string|null;

    /**
     * Resolves a type written with the GraphQL type system to its object representation.
     */
    public function resolveType(string $type): ?GraphQLType;
}
