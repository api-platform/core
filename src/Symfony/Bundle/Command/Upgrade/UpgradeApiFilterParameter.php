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
 * Resolved target of a single `#[ApiFilter]` declaration: the QueryParameter to emit.
 *
 * @internal
 */
final readonly class UpgradeApiFilterParameter
{
    /**
     * @param string               $key              the parameter key (query string name)
     * @param string               $filterClass      canonical replacement filter FQCN to instantiate
     * @param string|null          $property         explicit property when it differs from $key
     * @param string|null          $nativeType       scalar native type hint (bool|int|float|string), null to omit
     * @param bool                 $castToNativeType whether the QueryParameter should coerce the raw value
     * @param string|null          $filterContext    filter-specific config carried by the QueryParameter (e.g. the
     *                                               DateFilter null-management mode), null to omit
     * @param bool                 $caseSensitive    emit `caseSensitive: true` on the search filter (case-sensitive
     *                                               strategy); the new search filters are case-insensitive by default
     * @param array<string, mixed> $arguments        constructor arguments to pass to the (kept) filter, named
     */
    public function __construct(
        public string $key,
        public string $filterClass,
        public ?string $property = null,
        public ?string $nativeType = null,
        public bool $castToNativeType = false,
        public ?string $filterContext = null,
        public bool $caseSensitive = false,
        public array $arguments = [],
    ) {
    }
}
