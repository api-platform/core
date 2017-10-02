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
     * @param mixed       $value
     * @param string|null $resourceClass
     * @param bool        $strict
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getResourceClass($value, string $resourceClass = null, bool $strict = false): string;

    /**
     * Is the given class a resource class?
     *
     * @param string $type
     *
     * @return bool
     */
    public function isResourceClass(string $type): bool;
}
