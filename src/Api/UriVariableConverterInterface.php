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

namespace ApiPlatform\Api;

use ApiPlatform\Exception\InvalidUriVariableException;
use Symfony\Component\TypeInfo\Type;

interface UriVariableConverterInterface
{
    /**
     * Converts the value of a URI variable (identifier) to its type.
     *
     * @param mixed $value   The URI variable value to transform
     * @param array $types   The guessed type behind the URI variable
     * @param array $context Options available to the transformer
     *
     * @throws InvalidUriVariableException Occurs when the URI variable could not be converted
     */
    public function convert(mixed $value, Type $type, array $context = []);

    /**
     * Checks whether the value of a URI variable can be converted to its type by this converter.
     *
     * @param mixed $value   The URI variable value to transform
     * @param array $types   The types to which the URI variable value should be transformed
     * @param array $context Options available to the transformer
     */
    public function supportsConversion(mixed $value, Type $type, array $context = []): bool;
}
