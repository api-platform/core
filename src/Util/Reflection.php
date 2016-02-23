<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Util;

/**
 * Reflection utilities.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Reflection
{
    const ACCESSOR_PREFIXES = ['get', 'is', 'has', 'can'];
    const MUTATOR_PREFIXES = ['set', 'add', 'remove'];

    /**
     * Gets the property name associated with an accessor method.
     *
     * @param string $methodName
     *
     * @return string|null
     */
    public function getProperty($methodName)
    {
        $pattern = implode('|', array_merge(self::ACCESSOR_PREFIXES, self::MUTATOR_PREFIXES));

        if (preg_match('/^('.$pattern.')(.+)$/i', $methodName, $matches)) {
            return $matches[2];
        }
    }

    /**
     * Gets the {@see \ReflectionProperty} from the class or its parent.
     *
     * @param \ReflectionClass $reflectionClass
     * @param string           $attributeName
     *
     * @return \ReflectionProperty
     */
    private function getReflectionProperty(\ReflectionClass $reflectionClass, $attributeName)
    {
        if ($reflectionClass->hasProperty($attributeName)) {
            return $reflectionClass->getProperty($attributeName);
        }

        if ($parent = $reflectionClass->getParentClass()) {
            return $this->getReflectionProperty($parent, $attributeName);
        }
    }
}
