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
 * Maps a legacy Doctrine filter to its canonical QueryParameter replacement.
 *
 * Filters that survive (Date/Range/Exists) and custom/third-party filters are returned
 * unchanged — the codemod still wraps them in a `QueryParameter`, it just keeps the class.
 *
 * @internal
 */
final class UpgradeApiFilterMapper
{
    private const ORM_NAMESPACE = 'ApiPlatform\Doctrine\Orm\Filter';
    private const ODM_NAMESPACE = 'ApiPlatform\Doctrine\Odm\Filter';

    public function map(string $filterClass, ?string $strategy = null, ?string $propertyNativeType = null, bool $isRelation = false): UpgradeApiFilterMapping
    {
        $namespace = $this->driverNamespace($filterClass);

        // Custom / third-party filter: keep as-is, just wrap it in a QueryParameter.
        if (null === $namespace) {
            return new UpgradeApiFilterMapping($filterClass);
        }

        $shortName = substr($filterClass, \strlen($namespace) + 1);
        $canonical = static fn (string $name): string => $namespace.'\\'.$name;

        return match ($shortName) {
            'BooleanFilter' => new UpgradeApiFilterMapping($canonical('ExactFilter'), castToNativeType: true, nativeType: 'bool'),
            'NumericFilter' => new UpgradeApiFilterMapping($canonical('ExactFilter'), castToNativeType: true, nativeType: $propertyNativeType ?? 'int'),
            'BackedEnumFilter' => new UpgradeApiFilterMapping($canonical('ExactFilter'), castToNativeType: true, nativeType: $propertyNativeType),
            'OrderFilter' => new UpgradeApiFilterMapping($canonical('SortFilter')),
            'SearchFilter' => $this->searchReplacement($canonical, $strategy, $isRelation),
            default => new UpgradeApiFilterMapping($filterClass),
        };
    }

    /**
     * @param callable(string): string $canonical
     */
    private function searchReplacement(callable $canonical, ?string $strategy, bool $isRelation): UpgradeApiFilterMapping
    {
        if ($isRelation) {
            return new UpgradeApiFilterMapping($canonical('IriFilter'));
        }

        // A leading "i" makes the legacy strategy case-insensitive; the new search filters are
        // case-insensitive by default, so a case-sensitive (non-"i") strategy opts back in.
        $caseInsensitive = null !== $strategy && str_starts_with($strategy, 'i');
        $base = $caseInsensitive ? substr($strategy, 1) : $strategy;

        $shortName = match ($base) {
            'exact' => 'ExactFilter',
            'start' => 'StartSearchFilter',
            'end' => 'EndSearchFilter',
            'word_start' => 'WordStartSearchFilter',
            default => 'PartialSearchFilter',
        };

        // ExactFilter has no case-sensitivity option.
        $caseSensitive = 'ExactFilter' !== $shortName && !$caseInsensitive;

        return new UpgradeApiFilterMapping($canonical($shortName), caseSensitive: $caseSensitive);
    }

    private function driverNamespace(string $filterClass): ?string
    {
        return match (true) {
            str_starts_with($filterClass, self::ORM_NAMESPACE.'\\') => self::ORM_NAMESPACE,
            str_starts_with($filterClass, self::ODM_NAMESPACE.'\\') => self::ODM_NAMESPACE,
            default => null,
        };
    }
}
