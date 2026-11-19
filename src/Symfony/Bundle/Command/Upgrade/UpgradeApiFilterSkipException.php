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

namespace ApiPlatform\Symfony\Bundle\Command\Upgrade;

/**
 * Base class for the reasons a resource cannot be auto-migrated and is reported and skipped by the command.
 *
 * @internal
 */
abstract class UpgradeApiFilterSkipException extends \RuntimeException
{
}
