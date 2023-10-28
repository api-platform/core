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

namespace ApiPlatform\OpenApi\Serializer;

/**
 * Transforms the operation name to a readable operation id.
 *
 * @author soyuka <soyuka@gmail.com>
 */
trait NormalizeOperationNameTrait
{
    private function normalizeOperationName(string $operationName): string
    {
        return preg_replace('/^_/', '', str_replace(['/', '{._format}', '{', '}'], ['', '', '_', ''], $operationName));
    }
}
