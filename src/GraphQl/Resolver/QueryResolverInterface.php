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

namespace ApiPlatform\Core\GraphQl\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

/**
 * A function retrieving an item to resolve a GraphQL query.
 * Should return the normalized item or collection.
 *
 * @experimental
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
interface QueryResolverInterface
{
    /**
     * @return mixed|null The normalized query result (item or collection)
     */
    public function __invoke($source, $args, $context, ResolveInfo $info);
}
