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
 * Thrown when a filtered property is renamed by a configured name converter. The new overlay filters
 * do not denormalize the property (the parameter factory normalizes it), so such a resource cannot be
 * auto-migrated faithfully and is reported and skipped.
 *
 * @internal
 */
final class UpgradeApiFilterNameConversionException extends UpgradeApiFilterSkipException
{
    public function __construct(public readonly string $property)
    {
        parent::__construct(\sprintf('Cannot auto-migrate: property "%s" is renamed by a name converter, which the target filters do not support. Migrate this resource manually.', $property));
    }
}
