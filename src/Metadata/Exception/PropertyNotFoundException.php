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

use ApiPlatform\Exception\PropertyNotFoundException as LegacyPropertyNotFoundException;

if (class_exists(LegacyPropertyNotFoundException::class)) {
    class PropertyNotFoundException extends LegacyPropertyNotFoundException
    {
    }
} else {
    /**
     * Property not found exception.
     *
     * @author Kévin Dunglas <dunglas@gmail.com>
     */
    class PropertyNotFoundException extends \Exception implements ExceptionInterface
    {
    }
}
