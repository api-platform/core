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

namespace ApiPlatform\Metadata\Exception;

use ApiPlatform\Exception\InvalidIdentifierException as LegacyInvalidIdentifierException;

if (class_exists(LegacyInvalidIdentifierException::class)) {
    class InvalidIdentifierException extends LegacyInvalidIdentifierException
    {
    }
} else {
    /**
     * Identifier is not valid exception.
     *
     * @author Antoine Bluchet <soyuka@gmail.com>
     */
    final class InvalidIdentifierException extends \Exception implements ExceptionInterface
    {
    }
}
