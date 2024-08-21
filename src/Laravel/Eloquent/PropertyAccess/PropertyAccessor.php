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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @internal
 */
final class PropertyAccessor implements PropertyAccessorInterface
{
    private readonly PropertyAccessorInterface $inner;

    public function __construct(
        ?PropertyAccessorInterface $inner = null,
    ) {
        $this->inner = $inner ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param array<mixed, mixed>|object $objectOrArray
     */
    public function setValue(object|array &$objectOrArray, string|PropertyPathInterface $propertyPath, mixed $value): void
    {
        $this->inner->setValue($objectOrArray, $propertyPath, $value);
    }

    /**
     * @param array<mixed, mixed>|object $objectOrArray
     */
    public function getValue(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): mixed
    {
        $value = $this->inner->getValue($objectOrArray, $propertyPath);

        if ($value instanceof HasMany) {
            return $objectOrArray->{$propertyPath};
        }

        return $value;
    }

    /**
     * @param array<mixed, mixed>|object $objectOrArray
     */
    public function isWritable(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): bool
    {
        return $this->inner->isWritable($objectOrArray, $propertyPath);
    }

    /**
     * @param array<mixed, mixed>|object $objectOrArray
     */
    public function isReadable(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): bool
    {
        return $this->inner->isReadable($objectOrArray, $propertyPath);
    }
}
