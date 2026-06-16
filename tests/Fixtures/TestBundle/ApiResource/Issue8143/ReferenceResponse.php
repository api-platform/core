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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8143;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Reference;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/issue8143_reference_response',
            openapi: new Operation(
                responses: [
                    '401' => new Reference(ref: '#/components/responses/401'),
                ],
            ),
        ),
    ],
)]
final class ReferenceResponse
{
}
