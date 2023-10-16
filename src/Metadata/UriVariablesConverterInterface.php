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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Exception\InvalidIdentifierException;

if (interface_exists(\ApiPlatform\Api\UriVariablesConverterInterface::class)) {
    class_alias(
        \ApiPlatform\Api\UriVariablesConverterInterface::class,
        __NAMESPACE__.'\UriVariablesConverterInterface'
    );

    if (false) { // @phpstan-ignore-line
        interface UriVariablesConverterInterface extends \ApiPlatform\Api\UriVariablesConverterInterface
        {
        }
    }
} else {
    /**
     * Identifier converter.
     *
     * @author Antoine Bluchet <soyuka@gmail.com>
     */
    interface UriVariablesConverterInterface
    {
        /**
         * Takes an array of strings representing URI variables (identifiers) and transform their values to the expected type.
         *
         * @param array  $data  URI variables to convert to PHP values
         * @param string $class The class to which the URI variables belong to
         *
         * @throws InvalidIdentifierException
         *
         * @return array Array indexed by identifiers properties with their values denormalized
         */
        public function convert(array $data, string $class, array $context = []): array;
    }
}
