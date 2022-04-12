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

namespace ApiPlatform\Core\Bridge\Doctrine\EventListener;

class_exists(\ApiPlatform\Doctrine\EventListener\WriteListener::class);

if (false) {
    final class WriteListener extends \ApiPlatform\Doctrine\EventListener\WriteListener
    {
    }
}
