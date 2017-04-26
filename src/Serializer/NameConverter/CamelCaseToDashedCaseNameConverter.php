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

namespace ApiPlatform\Core\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * CamelCase to dashed name converter.
 *
 * Based on Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CamelCaseToDashedCaseNameConverter implements NameConverterInterface
{
    private $attributes;
    private $lowerCamelCase;

    /**
     * @param null|array $attributes     The list of attributes to rename or null for all attributes
     * @param bool       $lowerCamelCase Use lowerCamelCase style
     */
    public function __construct(array $attributes = null, bool $lowerCamelCase = true)
    {
        $this->attributes = $attributes;
        $this->lowerCamelCase = $lowerCamelCase;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($propertyName)
    {
        if (null === $this->attributes || in_array($propertyName, $this->attributes, true)) {
            $lcPropertyName = lcfirst($propertyName);
            $snakeCasedName = '';

            $len = strlen($lcPropertyName);
            for ($i = 0; $i < $len; ++$i) {
                if (ctype_upper($lcPropertyName[$i])) {
                    $snakeCasedName .= '-'.strtolower($lcPropertyName[$i]);
                } else {
                    $snakeCasedName .= strtolower($lcPropertyName[$i]);
                }
            }

            return $snakeCasedName;
        }

        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($propertyName)
    {
        $camelCasedName = preg_replace_callback('/(^|-|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '-' : '').strtoupper($match[2]);
        }, $propertyName);

        if ($this->lowerCamelCase) {
            $camelCasedName = lcfirst($camelCasedName);
        }

        if (null === $this->attributes || in_array($camelCasedName, $this->attributes, true)) {
            return $camelCasedName;
        }

        return $propertyName;
    }
}
