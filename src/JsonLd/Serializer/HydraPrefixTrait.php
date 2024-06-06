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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\JsonLd\ContextBuilder;

trait HydraPrefixTrait
{
    /**
     * @param array<string, mixed> $context
     */
    private function getHydraPrefix(array $context): string
    {
        return ($context[ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX] ?? true) ? ContextBuilder::HYDRA_PREFIX : '';
    }
}
