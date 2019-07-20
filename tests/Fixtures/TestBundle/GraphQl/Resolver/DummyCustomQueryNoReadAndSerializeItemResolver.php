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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\GraphQl\Resolver;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

/**
 * Resolver for dummy item custom query (item not read and no result serialized).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DummyCustomQueryNoReadAndSerializeItemResolver implements QueryItemResolverInterface
{
    /**
     * @param DummyCustomQuery|null $item
     *
     * @return DummyCustomQuery
     */
    public function __invoke($item, array $context)
    {
        if (null !== $item) {
            throw new RuntimeException('Item should be null');
        }

        return new DummyCustomQuery(); // Should not be normalized.
    }
}
