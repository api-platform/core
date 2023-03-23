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

use ApiPlatform\Exception\OperationNotFoundException as LegacyOperationNotFoundException;

if (class_exists(LegacyOperationNotFoundException::class)) {
    class OperationNotFoundException extends LegacyOperationNotFoundException
    {
    }
} else {
    /**
     * Operation not found exception.
     */
    class OperationNotFoundException extends \InvalidArgumentException implements ExceptionInterface
    {
    }
}
