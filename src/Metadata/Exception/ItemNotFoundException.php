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

use ApiPlatform\Exception\ItemNotFoundException as LegacyItemNotFoundException;

if (class_exists(LegacyItemNotFoundException::class)) {
    class ItemNotFoundException extends LegacyItemNotFoundException
    {
    }
} else {
    /**
     * Item not found exception.
     *
     * @author Amrouche Hamza <hamza.simperfit@gmail.com>
     */
    class ItemNotFoundException extends InvalidArgumentException
    {
    }
}
