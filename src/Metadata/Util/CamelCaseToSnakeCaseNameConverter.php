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

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Metadata\Util;

/**
 * CamelCase to Underscore name converter.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CamelCaseToSnakeCaseNameConverter
{
    private $attributes;
    private $lowerCamelCase;

    /**
     * @param array|null $attributes     The list of attributes to rename or null for all attributes
     * @param bool       $lowerCamelCase Use lowerCamelCase style
     */
    public function __construct(?array $attributes = null, bool $lowerCamelCase = true)
    {
        $this->attributes = $attributes;
        $this->lowerCamelCase = $lowerCamelCase;
    }

    public function normalize(string $propertyName): string
    {
        if (null === $this->attributes || \in_array($propertyName, $this->attributes, true)) {
            return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($propertyName)));
        }

        return $propertyName;
    }

    public function denormalize(string $propertyName): string
    {
        $camelCasedName = preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
        }, $propertyName);

        if ($this->lowerCamelCase) {
            $camelCasedName = lcfirst($camelCasedName);
        }

        if (null === $this->attributes || \in_array($camelCasedName, $this->attributes, true)) {
            return $camelCasedName;
        }

        return $propertyName;
    }
}
