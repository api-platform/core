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

use ApiPlatform\Test\ApiTestAssertionsTrait as BaseApiTestAssertionsTrait;

trigger_deprecation('api-platform/core', '5.0', 'The "%s" trait is deprecated, use "%s" instead.', ApiTestAssertionsTrait::class, BaseApiTestAssertionsTrait::class);

/**
 * @deprecated since API Platform 5.0, use {@see BaseApiTestAssertionsTrait} instead
 */
trait ApiTestAssertionsTrait
{
    use BaseApiTestAssertionsTrait;
}
