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

namespace ApiPlatform\Metadata;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsResourceMutator
{
    /**
     * @param class-string $resourceClass
     */
    public function __construct(
        public readonly string $resourceClass,
    ) {
    }
}
