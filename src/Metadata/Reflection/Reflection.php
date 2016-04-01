<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Reflection;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class Reflection implements ReflectionInterface
{
    const ACCESSOR_PREFIXES = ['get', 'is', 'has', 'can'];
    const MUTATOR_PREFIXES = ['set', 'add', 'remove'];

    private static $prefixes;

    /**
     * {@inheritdoc}
     */
    public function getAccessorPrefixes()
    {
        return self::ACCESSOR_PREFIXES;
    }

    /**
     * {@inheritdoc}
     */
    public function getMutatorPrefixes()
    {
        return self::MUTATOR_PREFIXES;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefixes()
    {
        if (null === self::$prefixes) {
            static::$prefixes = array_merge(self::ACCESSOR_PREFIXES, self::MUTATOR_PREFIXES);
        }

        return self::$prefixes;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($methodName)
    {
        $pattern = implode('|', self::getPrefixes());

        if (preg_match('/^('.$pattern.')(.+)$/i', $methodName, $matches)) {
            return $matches[2];
        }
    }
}
