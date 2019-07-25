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
use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCustomMutation as DummyCustomMutationDocument;

/**
 * Resolver for custom mutation (item not received and no result sent).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SumOnlyPersistDocumentMutationResolver implements MutationResolverInterface
{
    /**
     * @param DummyCustomMutationDocument|null $item
     */
    public function __invoke($item, array $context)
    {
        if (null !== $item) {
            throw new RuntimeException('Item should be null');
        }

        return new DummyCustomMutationDocument(); // Should not be normalized.
    }
}
