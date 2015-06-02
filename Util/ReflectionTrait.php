<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Util;

/**
 * Reflection utils.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ReflectionTrait
{
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
