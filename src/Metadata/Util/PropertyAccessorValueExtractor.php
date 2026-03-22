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

namespace ApiPlatform\Metadata\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class PropertyAccessorValueExtractor
{
    private static ?PropertyAccessorInterface $propertyAccessor = null;

    public static function getValue(object $object, string $property): string
    {
        self::$propertyAccessor ??= PropertyAccess::createPropertyAccessor();

        $value = self::$propertyAccessor->getValue($object, $property);
        if (\is_object($value) && method_exists($value, 'getId')) {
            $value = $value->getId();
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return 'null';
        }

        if ($value instanceof \Stringable || \is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, \JSON_THROW_ON_ERROR);
    }
}
