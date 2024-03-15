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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6151;

use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;

#[Post(
    uriTemplate: '/override_open_api_responses',
    openapi: new Operation(
        responses: [
            '204' => new Response(
                description: 'User activated',
            ),
        ]
    ),
    extraProperties: [OpenApiFactory::OVERRIDE_OPENAPI_RESPONSES => false],
)]
final class OverrideOpenApiResponses
{
}
