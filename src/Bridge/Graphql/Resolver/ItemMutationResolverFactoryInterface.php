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

namespace ApiPlatform\Core\Bridge\Graphql\Resolver;

/**
 * Creates a function resolving a GraphQL mutation of an item.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
interface ItemMutationResolverFactoryInterface
{
    public function createItemMutationResolver(string $resourceClass, string $mutationName): callable;
}
