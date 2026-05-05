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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/issue7945/import',
            policy: 'import',
            output: false,
            deserialize: false,
            status: 202,
            processor: [self::class, 'process'],
        ),
    ]
)]
class Issue7945
{
    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return null;
    }
}
