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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

#[ApiResource(operations: [
    new Post(uriTemplate: '/openapi_partial_update_resources', inputFormats: ['json' => ['application/json']]),
    new Put(uriTemplate: '/openapi_partial_update_resources/{id}', inputFormats: ['json' => ['application/json']], extraProperties: ['standard_put' => false]),
    new Patch(uriTemplate: '/openapi_partial_update_resources/{id}', inputFormats: ['json' => ['application/json']]),
])]
final class OpenApiPartialUpdateResource
{
    public ?int $id = null;

    #[ApiProperty(required: true)]
    public ?string $name = null;
}
