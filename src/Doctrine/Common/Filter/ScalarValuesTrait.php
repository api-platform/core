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

trait ScalarValuesTrait
{
    private function scalarValuesOnly(mixed $values): mixed
    {
        if (!\is_array($values)) {
            return $values;
        }

        $scalars = [];
        foreach ($values as $value) {
            if (\is_scalar($value)) {
                $scalars[] = $value;
            }
        }

        return $scalars;
    }
}
