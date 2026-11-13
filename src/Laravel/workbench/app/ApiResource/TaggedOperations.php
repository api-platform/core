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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/tagged_operations',
            openapi: new OpenApiOperation(extensionProperties: ['x-apiplatform-tag' => ['internal']]),
            provider: [self::class, 'provide'],
        ),
        new Get(
            uriTemplate: '/tagged_operations/{id}',
            provider: [self::class, 'provide'],
        ),
    ]
)]
class TaggedOperations
{
    public int $id = 1;

    public static function provide(): array
    {
        return [];
    }
}
