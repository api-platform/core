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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;

/**
 * An ItemProvider that just creates a new object.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @experimental
 */
final class ObjectProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        try {
            return new ($operation->getClass());
        } catch (\Throwable $e) {
            throw new RuntimeException(\sprintf('An error occurred while trying to create an instance of the "%s" resource. Consider writing your own "%s" implementation and setting it as `provider` on your operation instead.', $operation->getClass(), ProviderInterface::class), 0, $e);
        }
    }
}
