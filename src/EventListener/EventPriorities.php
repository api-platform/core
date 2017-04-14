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

namespace ApiPlatform\Core\EventListener;

/**
 * Constants for common priorities.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EventPriorities
{
    const PRE_READ = 5;
    const POST_READ = 3;
    const PRE_DESERIALIZE = 3;
    const POST_DESERIALIZE = 1;
    const PRE_VALIDATE = 65;
    const POST_VALIDATE = 63;
    const PRE_WRITE = 33;
    const POST_WRITE = 31;
    const PRE_RESPOND = 9;
    const POST_RESPOND = 0;
}
