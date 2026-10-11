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
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;

#[ApiResource(
    operations: [
        new Post(
            status: 201,
            openapi: new Operation(
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'hello world' => new Example(description: 'Submitting a "hello world" entity', value: ['id' => 1, 'name' => 'Hello world']),
                            ]),
                        ),
                        'application/json' => [
                            'examples' => [
                                'foo bar' => ['description' => 'Submitting a "foo bar" entity', 'value' => ['id' => 2, 'name' => 'Foo bar']],
                            ],
                        ],
                    ]),
                ),
            ),
        ),
    ],
)]
class RequestBodyWithExamplesResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public ?int $id = null,
        public ?string $name = null,
    ) {
    }
}
