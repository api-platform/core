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

use ApiPlatform\Exception\RuntimeException as LegacyRuntimeException;

if (class_exists(LegacyRuntimeException::class)) {
    class RuntimeException extends LegacyRuntimeException
    {
    }
} else {
    /**
     * Runtime exception.
     *
     * @author Kévin Dunglas <dunglas@gmail.com>
     */
    class RuntimeException extends \RuntimeException implements ExceptionInterface
    {
    }
}
