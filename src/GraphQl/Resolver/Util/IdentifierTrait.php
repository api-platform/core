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

namespace ApiPlatform\GraphQl\Resolver\Util;

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Subscription;

/**
 * Identifier helper methods.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait IdentifierTrait
{
    private function getIdentifierFromContext(array $context): ?string
    {
        $args = $context['args'];

        if ($context['is_mutation'] || $context['is_subscription']) {
            return $args['input']['id'] ?? null;
        }

        return $args['id'] ?? null;
    }

    /**
     * @param array<string, mixed> $args
     */
    private function getIdentifierFromOperation(Operation $operation, array $args): ?string
    {
        if ($operation instanceof Subscription || $operation instanceof Mutation) {
            return $args['input']['id'] ?? null;
        }

        return $args['id'] ?? null;
    }
}
