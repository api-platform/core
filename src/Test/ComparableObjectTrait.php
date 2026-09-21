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

namespace ApiPlatform\Test;

/**
 * Projects an object graph onto nested arrays, so that two structurally equal but
 * distinct graphs can be compared with assertSame(), which compares objects by identity.
 */
trait ComparableObjectTrait
{
    private static function toComparableArray(mixed $value): mixed
    {
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        } elseif (\is_object($value)) {
            $value = [$value::class => get_mangled_object_vars($value)];
        }

        if (\is_array($value)) {
            return array_map(self::toComparableArray(...), $value);
        }

        return $value;
    }
}
