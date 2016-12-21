<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Extractor;

use ApiPlatform\Core\Swagger\Util\SwaggerOperationDataGuard;

final class SwaggerContextOperationExtractor implements SwaggerOperationExtractorInterface
{
    public function extract(array $operationData): \ArrayObject
    {
        return new \ArrayObject([
            $operationData['path'] => [
                strtolower($operationData['method']) => new \ArrayObject($operationData['operation']['swagger_context']),
            ],
        ]);
    }

    public function supportsExtraction(array $operationData): bool
    {
        return SwaggerOperationDataGuard::check($operationData) && array_key_exists('swagger_context', $operationData['operation']);
    }
}
