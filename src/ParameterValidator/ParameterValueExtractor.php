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

namespace ApiPlatform\ParameterValidator;

/**
 * Extract values from parameters.
 *
 * @internal
 *
 * @author Nicolas LAURENT <nicolas.laurent@les-tilleuls.coop>
 */
class ParameterValueExtractor
{
    /**
     * @param int|int[]|string|string[] $value
     *
     * @return int[]|string[]
     */
    public static function getValue(int|string|array $value, string $collectionFormat = 'csv'): array
    {
        if (\is_array($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return explode(self::getSeparator($collectionFormat), $value);
        }

        return [$value];
    }

    /** @return non-empty-string */
    public static function getSeparator(string $collectionFormat): string
    {
        return match ($collectionFormat) {
            'csv' => ',',
            'ssv' => ' ',
            'tsv' => '\t',
            'pipes' => '|',
            default => throw new \InvalidArgumentException(sprintf('Unknown collection format %s', $collectionFormat)),
        };
    }

    /**
     * @param array<string, array<string, mixed>> $filterDescription
     */
    public static function getCollectionFormat(array $filterDescription): string
    {
        return $filterDescription['openapi']['collectionFormat'] ?? $filterDescription['swagger']['collectionFormat'] ?? 'csv';
    }

    /**
     * @param array<string, mixed> $queryParameters
     *
     * @throws \InvalidArgumentException
     */
    public static function iterateValue(string $name, array $queryParameters, string $collectionFormat = 'csv'): \Generator
    {
        foreach ($queryParameters as $key => $value) {
            if ($key === $name || "{$key}[]" === $name) {
                $values = self::getValue($value, $collectionFormat);
                foreach ($values as $v) {
                    yield [$key => $v];
                }
            }
        }
    }
}
