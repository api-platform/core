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

namespace ApiPlatform\Metadata;

/**
 * Names a subtype of a discriminator map that is not a resource, in JSON-LD and Hydra.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApiSubtype
{
    public function __construct(public readonly string $shortName)
    {
    }
}
