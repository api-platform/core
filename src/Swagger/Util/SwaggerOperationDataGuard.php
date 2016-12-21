<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Util;

class SwaggerOperationDataGuard
{
    public static function check(array $operationData): bool
    {
        return array_key_exists('resourceClass', $operationData) &&
        array_key_exists('operationName', $operationData) &&
        array_key_exists('operation', $operationData) &&
        array_key_exists('isCollection', $operationData) &&
        array_key_exists('path', $operationData) &&
        array_key_exists('method', $operationData) &&
        array_key_exists('mimeTypes', $operationData);
    }
}
