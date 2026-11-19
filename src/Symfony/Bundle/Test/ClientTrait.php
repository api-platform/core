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

namespace ApiPlatform\Symfony\Bundle\Test;

use ApiPlatform\Test\ClientTrait as BaseClientTrait;

trigger_deprecation('api-platform/core', '5.0', 'The "%s" trait is deprecated, use "%s" instead.', ClientTrait::class, BaseClientTrait::class);

/**
 * @deprecated since API Platform 5.0, use {@see BaseClientTrait} instead
 */
trait ClientTrait
{
    use BaseClientTrait;
}
