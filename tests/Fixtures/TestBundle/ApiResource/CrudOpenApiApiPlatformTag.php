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
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;

#[ApiResource(
    description: 'Something nice',
    operations: [
        new Get(openapi: new Operation(extensionProperties: [OpenApiFactory::API_PLATFORM_TAG => ['anotherone']])),
    ]
)]
class CrudOpenApiApiPlatformTag
{
    public string $id;
}
