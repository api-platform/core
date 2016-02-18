<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Exception\InvalidArgumentException;

/**
 * Guesses which resource is associated with a given object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceClassResolverInterface
{
    /**
     * Guesses the associated resource.
     *
     * @param mixed       $value
     * @param string|null $resourceClass
     * @param bool        $strict
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getResourceClass($value, string $resourceClass = null, bool $strict = false) : string;

    /**
     * Is the given class a resource class?
     *
     * @param string $type
     *
     * @return bool
     */
    public function isResourceClass(string $type) : bool;
}
