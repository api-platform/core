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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class SwaggerOperationId
{
    public static function create($operationData, ResourceMetadata $resourceMetadata): string
    {
        $operationId = lcfirst($operationData['operationName']);
        $operationId .= ucfirst($resourceMetadata->getShortName());
        $operationId .= ucfirst($operationData['isCollection'] ? 'collection' : 'item');

        return $operationId;
    }
}
