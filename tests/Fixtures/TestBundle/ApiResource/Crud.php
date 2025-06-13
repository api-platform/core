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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;

#[ApiResource(
    description: 'A resource used for OpenAPI tests.',
    operations: [
        new Get(),
        new GetCollection(openapi: new Operation(extensionProperties: [OpenApiFactory::API_PLATFORM_TAG => ['internal', 'anotherone']])),
        new Post(openapi: new Operation(extensionProperties: [OpenApiFactory::API_PLATFORM_TAG => 'internal'])),
        new Patch(),
        new Delete(),
    ]
)]
class Crud
{
    public string $id;
}
