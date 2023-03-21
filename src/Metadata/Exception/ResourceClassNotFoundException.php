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

use ApiPlatform\Exception\ResourceClassNotFoundException as LegacyResourceClassNotFoundException;

if (class_exists(LegacyResourceClassNotFoundException::class)) {
    class ResourceClassNotFoundException extends LegacyResourceClassNotFoundException
    {
    }
} else {
    /**
     * Resource class not found exception.
     *
     * @author Kévin Dunglas <dunglas@gmail.com>
     */
    class ResourceClassNotFoundException extends \Exception implements ExceptionInterface
    {
    }
}
