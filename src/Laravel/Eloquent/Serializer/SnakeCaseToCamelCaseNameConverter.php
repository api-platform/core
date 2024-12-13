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

namespace ApiPlatform\Laravel\Eloquent\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Underscore to cameCase name converter.
 *
 * @internal
 *
 * @see Adapted from https://github.com/symfony/symfony/blob/7.2/src/Symfony/Component/Serializer/NameConverter/CamelCaseToSnakeCaseNameConverter.php.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Aurélien Pillevesse <aurelienpillevesse@hotmail.fr>
 * @copyright Fabien Potencier <fabien@symfony.com>
 */
final class SnakeCaseToCamelCaseNameConverter implements NameConverterInterface
{
    /**
     * @param string[]|null $attributes The list of attributes to rename or null for all attributes
     */
    public function __construct(
        private readonly ?array $attributes = null,
    ) {
    }

    /**
     * @param class-string|null    $class
     * @param array<string, mixed> $context
     */
    public function normalize(
        string $propertyName, ?string $class = null, ?string $format = null, array $context = [],
    ): string {
        if (null === $this->attributes || \in_array($propertyName, $this->attributes, true)) {
            return lcfirst(preg_replace_callback(
                '/(^|_|\.)+(.)/',
                fn ($match) => ('.' === $match[1] ? '_' : '').strtoupper($match[2]),
                $propertyName
            ));
        }

        return $propertyName;
    }

    /**
     * @param class-string|null    $class
     * @param array<string, mixed> $context
     */
    public function denormalize(
        string $propertyName, ?string $class = null, ?string $format = null, array $context = [],
    ): string {
        $snakeCased = strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($propertyName)));
        if (null === $this->attributes || \in_array($snakeCased, $this->attributes, true)) {
            return $snakeCased;
        }

        return $propertyName;
    }
}
