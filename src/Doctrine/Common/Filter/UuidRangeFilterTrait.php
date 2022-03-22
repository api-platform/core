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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Exception\InvalidArgumentException;

/**
 * Trait for filtering the collection by range using UUIDs (UUID v6).
 *
 * @author Kai Dederichs <kai.dederichs@protonmail.com>
 */
trait UuidRangeFilterTrait
{
    use RangeFilterTrait;

    /**
     * {@inheritdoc}
     */
    protected function normalizeBetweenValues(array $values): ?array
    {
        if (2 !== \count($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid format for "[%s]", expected "<min>..<max>"', self::PARAMETER_BETWEEN)),
            ]);

            return null;
        }

        if (!$this->isValidUid($values[0]) || !$this->isValidUid($values[1])) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid values for "[%s]" range, expected uuids', self::PARAMETER_BETWEEN)),
            ]);

            return null;
        }

        return [$values[0], $values[1]];
    }

    /**
     * Normalize the value.
     */
    protected function normalizeValue(string $value, string $operator): ?string
    {
        if (!$this->isValidUid($value)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid value for "[%s]", expected number', $operator)),
            ]);

            return null;
        }

        return $value;
    }

    private function isValidUid($potentialUid): bool
    {
        return \is_string($potentialUid) && preg_match('{^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$}Di', $potentialUid);
    }
}
