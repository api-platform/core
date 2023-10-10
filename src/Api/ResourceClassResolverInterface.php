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

namespace ApiPlatform\Api;

class_exists(\ApiPlatform\Metadata\ResourceClassResolverInterface::class);

if (false) { // @phpstan-ignore-line
    interface ResourceClassResolverInterface extends \ApiPlatform\Metadata\ResourceClassResolverInterface
    {
    }
}
