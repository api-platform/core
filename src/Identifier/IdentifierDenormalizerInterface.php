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

namespace ApiPlatform\Core\Identifier;

interface IdentifierDenormalizerInterface
{
    /**
     * Takes an array of identifiers and transform their values from strings to the expected type.
     */
    public function denormalize($identifiers, $class, ?string $format = null, array $context = []): array;
}
