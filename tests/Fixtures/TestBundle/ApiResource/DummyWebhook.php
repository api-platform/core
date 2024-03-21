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
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;

#[ApiResource(operations: [new Get(openapi: new Webhook(
    name: 'a',
    pathItem: new PathItem(
        get: new Operation(
            summary: 'Something else here',
            description: 'Something else here for example',
        ),
    )
)), new Post(openapi: new Webhook(
    name: 'b',
    pathItem: new PathItem(
        post: new Operation(
            summary: 'Something else here',
            description: 'Hi! it\'s me, I\'m the problem, it\'s me',
        ),
    )
)),
])]
class DummyWebhook
{
    public $id;
}
