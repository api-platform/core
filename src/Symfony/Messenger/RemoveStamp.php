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

namespace ApiPlatform\Symfony\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Hints that the resource in the envelope must be removed.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RemoveStamp implements StampInterface
{
}

class_alias(RemoveStamp::class, \ApiPlatform\Core\Bridge\Symfony\Messenger\RemoveStamp::class);
