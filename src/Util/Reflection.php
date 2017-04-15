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

namespace ApiPlatform\Core\Util;

/**
 * Reflection utilities.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Reflection
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

        return null;
    }
}
