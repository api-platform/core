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
 * Runs an API Platform operation on a controller method bound to the application's own route.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ApiOperation
{
    public function __construct(public readonly HttpOperation $operation)
    {
    }
}
