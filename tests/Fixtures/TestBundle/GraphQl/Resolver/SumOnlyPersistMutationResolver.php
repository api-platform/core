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

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomMutation;

/**
 * Resolver for custom mutation (item not received and no result sent).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SumOnlyPersistMutationResolver implements MutationResolverInterface
{
    /**
     * @param DummyCustomMutation|null $item
     */
    public function __invoke($item, array $context)
    {
        if (null !== $item) {
            throw new RuntimeException('Item should be null');
        }

        return new DummyCustomMutation(); // Should not be normalized.
    }
}
