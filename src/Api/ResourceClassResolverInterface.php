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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;

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
     * @param mixed  $value         Object you're playing with
     * @param string $resourceClass Resource class it is supposed to be (could be parent class for instance)
     * @param bool   $strict        value must be type of resource class given or it will return type
     *
     * @throws InvalidArgumentException
     *
     * @return string Resolved resource class
     */
    public function getResourceClass($value, string $resourceClass = null, bool $strict = false): string;

    /**
     * Is the given class name an api platform resource?
     *
     * @param string $type FQCN of the resource
     */
    public function isResourceClass(string $type): bool;
}
