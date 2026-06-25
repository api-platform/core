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
 * Thrown when two legacy filters on a resource resolve to the same QueryParameter key
 * (e.g. an exact and a range filter on one property), which cannot be expressed as two
 * QueryParameters. Such resources are skipped by the command and handled separately.
 *
 * @internal
 */
final class UpgradeApiFilterCollisionException extends UpgradeApiFilterSkipException
{
    public function __construct(public readonly string $parameterKey)
    {
        parent::__construct(\sprintf('Cannot auto-migrate: two filters resolve to the same QueryParameter key "%s".', $parameterKey));
    }
}
