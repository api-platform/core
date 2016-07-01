<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Action;

/**
 * Empty action. Useful to trigger the kernel.view event without doing anything specific in the action
 * (e.g. the POST action).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class NullAction
{
    public function __invoke()
    {
    }
}
