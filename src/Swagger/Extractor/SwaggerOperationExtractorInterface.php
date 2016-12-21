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

interface SwaggerOperationExtractorInterface
{
    public function extract(array $operationData): \ArrayObject;

    public function supportsExtraction(array $operationData): bool;
}
