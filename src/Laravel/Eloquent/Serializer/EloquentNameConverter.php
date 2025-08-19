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

namespace ApiPlatform\Laravel\Eloquent\Serializer;

use Symfony\Component\Serializer\Exception\UnexpectedPropertyException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class EloquentNameConverter implements NameConverterInterface
{
    public function __construct(private readonly NameConverterInterface $nameConverter)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        try {
            return $this->nameConverter->normalize($propertyName, $class, $format, $context); // @phpstan-ignore-line
        } catch (UnexpectedPropertyException $e) {
            return $this->nameConverter->denormalize($propertyName, $class, $format, $context); // @phpstan-ignore-line
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        try {
            return $this->nameConverter->denormalize($propertyName, $class, $format, $context); // @phpstan-ignore-line
        } catch (UnexpectedPropertyException $e) {
            return $this->nameConverter->normalize($propertyName, $class, $format, $context); // @phpstan-ignore-line
        }
    }
}
