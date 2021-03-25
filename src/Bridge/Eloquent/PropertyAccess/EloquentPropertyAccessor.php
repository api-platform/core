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

namespace ApiPlatform\Core\Bridge\Eloquent\PropertyAccess;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Writes and reads values to/from an Eloquent model.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EloquentPropertyAccessor implements PropertyAccessorInterface
{
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$objectOrArray, $propertyPath, $value): void
    {
        if (\is_object($objectOrArray) && is_subclass_of($objectOrArray, Model::class, true)) {
            $reflectionClass = new \ReflectionClass($objectOrArray);
            if ($reflectionClass->hasMethod($propertyPath)) {
                if (\is_array($value)) {
                    $value = new Collection($value);
                }
                $objectOrArray->setRelation($propertyPath, $value);

                return;
            }
        }

        $this->propertyAccessor->setValue($objectOrArray, $propertyPath, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        if (!\is_object($objectOrArray) || !is_subclass_of($objectOrArray, Model::class, true)) {
            return $this->propertyAccessor->getValue($objectOrArray, $propertyPath);
        }

        $value = $objectOrArray->getAttribute($propertyPath);

        if ($value instanceof Carbon) {
            return $value->toDate();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($objectOrArray, $propertyPath): bool
    {
        return $this->propertyAccessor->isWritable($objectOrArray, $propertyPath);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($objectOrArray, $propertyPath): bool
    {
        return $this->propertyAccessor->isReadable($objectOrArray, $propertyPath);
    }
}
