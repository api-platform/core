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

use ApiPlatform\Exception\InvalidArgumentException as LegacyInvalidArgumentException;

if (class_exists(LegacyInvalidArgumentException::class)) {
    class InvalidIdentifierException extends LegacyInvalidArgumentException
    {
    }
} else {
    /**
     * Identifier is not valid exception.
     *
     * @author Antoine Bluchet <soyuka@gmail.com>
     */
    class InvalidIdentifierException extends \Exception implements ExceptionInterface
    {
    }
}
