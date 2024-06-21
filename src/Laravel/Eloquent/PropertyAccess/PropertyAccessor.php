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

namespace ApiPlatform\Laravel\Eloquent\PropertyAccess;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @internal
 */
final class PropertyAccessor implements PropertyAccessorInterface
{
    public function __construct(private readonly PropertyAccessorInterface $inner)
    {
    }

    public function setValue(object|array &$objectOrArray, string|PropertyPathInterface $propertyPath, mixed $value): void
    {
        $this->inner->setValue($objectOrArray, $propertyPath, $value);
    }

    public function getValue(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): mixed
    {
        $value = $this->inner->getValue($objectOrArray, $propertyPath);

        if ($value instanceof HasMany) {
            return $objectOrArray->{$propertyPath};
        }

        return $value;
    }

    public function isWritable(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): bool
    {
        return $this->inner->isWritable($objectOrArray, $propertyPath);
    }

    public function isReadable(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): bool
    {
        return $this->inner->isReadable($objectOrArray, $propertyPath);
    }
}
