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

use GraphQL\Type\Definition\Type as GraphQLType;
use Psr\Container\ContainerInterface;

/**
 * Interface implemented to contain the GraphQL types.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface TypesContainerInterface extends ContainerInterface
{
    /**
     * Sets a type.
     *
     * @param string      $id   The type identifier
     * @param GraphQLType $type The type instance
     */
    public function set(string $id, GraphQLType $type): void;

    /**
     * Gets a type.
     *
     * @throws TypeNotFoundException When a type has not been found
     *
     * @return GraphQLType The type found in the container
     */
    public function get(string $id): GraphQLType;

    /**
     * Gets all the types.
     *
     * @return array An array of types
     */
    public function all(): array;

    /**
     * Returns true if the given type is present in the container.
     */
    public function has(string $id): bool;
}
