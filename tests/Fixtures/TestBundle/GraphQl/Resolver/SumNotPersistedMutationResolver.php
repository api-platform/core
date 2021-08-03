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

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomMutation as DummyCustomMutationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomMutation;

/**
 * Resolver for custom mutation (item not persisted).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SumNotPersistedMutationResolver implements MutationResolverInterface
{
    /**
     * @param DummyCustomMutation|DummyCustomMutationDocument|null $item
     */
    public function __invoke($item, array $context)
    {
        // Side-effect.

        return null;
    }
}
