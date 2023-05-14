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

namespace ApiPlatform\GraphQl\Resolver;

/**
 * A function resolving a GraphQL mutation.
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
interface MutationResolverInterface
{
    public function __invoke(?object $item, array $context): ?object;
}
