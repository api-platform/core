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

namespace ApiPlatform\Tests\Fixtures\TestBundle\GraphQl\Resolver;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

/**
 * Resolver for dummy item custom query.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
class DummyCustomQueryNotRetrievedItemResolver implements QueryItemResolverInterface
{
    public function __invoke(?object $item, array $context): DummyCustomQuery
    {
        if (null === $item) {
            $item = new DummyCustomQuery();
            $item->id = 0;
            $item->message = 'Success (not retrieved)!';

            return $item;
        }

        return $item;
    }
}
