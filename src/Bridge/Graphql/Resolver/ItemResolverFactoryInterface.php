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
 * Creates a function retrieving an item to resolve a GraphQL query.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
interface ItemResolverFactoryInterface
{
    public function createItemResolver(string $resourceClass, string $rootClass, string $operationName): callable;
}
