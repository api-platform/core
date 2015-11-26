<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Event;

/**
 * Events.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class Events
{
    /**
     * The SAVE_ERROR event occurs when an error occurred on save.
     */
    const SAVE_ERROR = 'api.save_error';
}
