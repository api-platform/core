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

namespace ApiPlatform\GraphQl\Util;

/**
 * @internal
 */
trait ArrayTrait
{
    public function isSequentialArrayOfArrays(array $array): bool
    {
        if (!$this->isSequentialArray($array)) {
            return false;
        }

        return $this->arrayContainsOnly($array, 'array');
    }

    public function isSequentialArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_is_list($array);
    }

    public function arrayContainsOnly(array $array, string $type): bool
    {
        return $array === array_filter($array, static fn ($item): bool => $type === \gettype($item));
    }
}
