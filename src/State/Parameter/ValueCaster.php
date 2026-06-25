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

use ApiPlatform\Metadata\Exception\BadRequestException;

/**
 * Caster returns the value unchanged when it can not be casted, so constraint validation can
 * reject it. An empty string is the exception: it can not represent a scalar native type, so we
 * throw a Bad Request (400) rather than letting it reach the filter as a raw, untyped value.
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
            '' => throw new BadRequestException('An empty value cannot be cast to a boolean.'),
            default => $v,
        };
    }

    public static function toInt(mixed $v): mixed
    {
        if (\is_int($v)) {
            return $v;
        }

        if ('' === $v) {
            throw new BadRequestException('An empty value cannot be cast to an integer.');
        }

        $value = filter_var($v, \FILTER_VALIDATE_INT);

        return false === $value ? $v : $value;
    }

    public static function toFloat(mixed $v): mixed
    {
        if (\is_float($v)) {
            return $v;
        }

        if ('' === $v) {
            throw new BadRequestException('An empty value cannot be cast to a float.');
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
}
