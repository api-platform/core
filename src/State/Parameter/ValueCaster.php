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

namespace ApiPlatform\State\Parameter;

use ApiPlatform\Metadata\Parameter;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Caster returns the value unchanged when it can not be casted, so constraint validation can
 * reject it. Therefore we do not need to throw exceptions, validation will just fail.
 *
 * @internal
 */
final class ValueCaster
{
    public static function toBool(mixed $v): mixed
    {
        if (!\is_string($v)) {
            return $v;
        }

        return match (strtolower($v)) {
            '1', 'true' => true,
            '0', 'false' => false,
            default => $v,
        };
    }

    public static function toInt(mixed $v): mixed
    {
        if (\is_int($v)) {
            return $v;
        }

        $value = filter_var($v, \FILTER_VALIDATE_INT);

        return false === $value ? $v : $value;
    }

    public static function toFloat(mixed $v): mixed
    {
        if (\is_float($v)) {
            return $v;
        }

        $value = filter_var($v, \FILTER_VALIDATE_FLOAT);

        return false === $value ? $v : $value;
    }

    public static function toDateTime(mixed $v): mixed
    {
        if ($v instanceof \DateTimeInterface) {
            return $v;
        }

        if (!\is_string($v)) {
            return $v;
        }

        try {
            return new \DateTimeImmutable($v);
        } catch (\Exception) {
            return $v;
        }
    }

    public static function toBackedEnum(mixed $v, ?Parameter $parameter = null): mixed
    {
        $type = null !== $parameter?->getNativeType() ? self::resolveEnumType($parameter->getNativeType()) : null;
        if (!$type instanceof BackedEnumType || (!\is_string($v) && !\is_int($v))) {
            return $v;
        }

        $value = $v;
        if ($type->getBackingType()->isIdentifiedBy(TypeIdentifier::INT)) {
            $value = filter_var($v, \FILTER_VALIDATE_INT);
            if (false === $value) {
                return $v;
            }
        }

        $class = $type->getClassName();

        return $class::tryFrom($value) ?? $v;
    }

    public static function resolveEnumType(Type $type): ?EnumType
    {
        if ($type instanceof EnumType) {
            return $type;
        }

        if ($type instanceof CollectionType) {
            return self::resolveEnumType($type->getCollectionValueType());
        }

        if ($type instanceof NullableType) {
            return self::resolveEnumType($type->getWrappedType());
        }

        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $t) {
                if ($enum = self::resolveEnumType($t)) {
                    return $enum;
                }
            }
        }

        return null;
    }
}
