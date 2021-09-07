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

use ApiPlatform\Exception\InvalidIdentifierException;

/**
 * Identifier converter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface UriVariablesConverterInterface
{
    /**
     * Takes an array of strings representing identifiers and transform their values to the expected type.
     *
     * @param array  $data  Identifier to convert to php values
     * @param string $class The class to which the identifiers belong to
     *
     * @throws InvalidIdentifierException
     *
     * @return array indexed by identifiers properties with their values denormalized
     */
    public function convert(array $data, string $class, array $context = []): array;
}
