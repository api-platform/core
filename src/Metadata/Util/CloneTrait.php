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

/**
 * Clones given data if cloneable.
 *
 * @internal
 *
 * @author Quentin Barloy <quentin.barloy@gmail.com>
 */
trait CloneTrait
{
    public function clone(mixed $data): mixed
    {
        if (is_array($data)) {
            return $this->cloneArray($data);
        }

        if (!is_object($data)) {
            return $data;
        }

        try {
            $reflection = new \ReflectionClass($data);

            if (!$reflection->isCloneable()) {
                return null;
            }

            $clonedObject = clone $data;

            foreach ($reflection->getProperties() as $property) {
                if ($property->isInitialized($data)) {
                    $value = $property->getValue($data);

                    if (is_object($value)) {
                        $clonedValue = $this->clone($value);
                        $property->setValue($clonedObject, $clonedValue);
                    } elseif (is_array($value)) {
                        $clonedValue = $this->cloneArray($value);
                        $property->setValue($clonedObject, $clonedValue);
                    } else {
                        $property->setValue($clonedObject, $value);
                    }
                }

                // If the property is uninitialized, we skip it to avoid errors
            }

            return $clonedObject;
        } catch (\ReflectionException) {
            return null;
        }
    }

    private function cloneArray(array $array): array
    {
        $clonedArray = [];
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $clonedArray[$key] = $this->clone($value);
            } elseif (is_array($value)) {
                $clonedArray[$key] = $this->cloneArray($value);
            } else {
                $clonedArray[$key] = $value;
            }
        }
        return $clonedArray;
    }
}
