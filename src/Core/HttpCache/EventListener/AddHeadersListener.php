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

namespace ApiPlatform\Core\HttpCache\EventListener;

class_exists(\ApiPlatform\HttpCache\EventListener\AddHeadersListener::class);

if (false) {
    final class AddHeadersListener extends \ApiPlatform\HttpCache\EventListener\AddHeadersListener
    {
    }
}
