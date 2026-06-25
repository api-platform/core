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

namespace ApiPlatform\Symfony\Bundle\Command\Upgrade;

/**
 * Canonical replacement for a single legacy filter, resolved by {@see UpgradeApiFilterMapper}.
 *
 * @internal
 */
final readonly class UpgradeApiFilterMapping
{
    public function __construct(
        public string $filterClass,
        public bool $castToNativeType = false,
        public ?string $nativeType = null,
        public bool $caseSensitive = false,
    ) {
    }
}
