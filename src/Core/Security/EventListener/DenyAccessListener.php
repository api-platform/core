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

namespace ApiPlatform\Core\Security\EventListener;

class_exists(\ApiPlatform\Symfony\EventListener\DenyAccessListener::class);

if (false) {
    final class DenyAccessListener extends \ApiPlatform\Symfony\EventListener\DenyAccessListener
    {
    }
}
