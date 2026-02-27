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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiErrorTestResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiRequiredFieldsResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class JsonApiTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            JsonApiErrorTestResource::class,
            JsonApiInputResource::class,
            JsonApiRequiredFieldsResource::class,
        ];
    }

    public function testError(): void
    {
        self::createClient()->request('GET', '/jsonapi_error_test/nonexistent', [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'errors' => [
                [
                    // TODO: change this to '400' in 5.x
                    'status' => 400,
                    'detail' => 'Resource "nonexistent" not found.',
                ],
            ],
        ]);
    }

    /**
     * Reproducer for https://github.com/api-platform/core/issues/7794.
     *
     * When using an input DTO with JSON:API format, the JsonApi\ItemNormalizer
     * must not unwrap data.attributes twice. Without the fix, the second pass
     * reads $data['data']['attributes'] from already-flat data and gets null,
     * which nulls every DTO property.
     */
    public function testPostWithInputDtoPreservesAttributes(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_input_test', [
            'headers' => [
                'accept' => 'application/vnd.api+json',
                'content-type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiInputResource',
                    'attributes' => [
                        'title' => 'Hello from JSON:API',
                        'body' => 'This should not be nulled.',
                    ],
                ],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                'attributes' => [
                    'title' => 'Hello from JSON:API',
                    'body' => 'This should not be nulled.',
                ],
            ],
        ]);
    }

    /**
     * Verify that a JSON:API POST with all required fields on an input DTO
     * with constructor arguments works correctly end-to-end.
     *
     * Related to Sylius test failures caused by a missing `continue` in
     * AbstractItemNormalizer::instantiateObject() — only the first missing
     * constructor argument was reported instead of all of them.
     */
    public function testPostWithRequiredConstructorArgsInputDto(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_required_fields_test', [
            'headers' => [
                'accept' => 'application/vnd.api+json',
                'content-type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiRequiredFieldsResource',
                    'attributes' => [
                        'title' => 'Great review',
                        'rating' => 5,
                        'comment' => 'Loved it.',
                    ],
                ],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'data' => [
                'attributes' => [
                    'title' => 'Great review',
                    'rating' => 5,
                    'comment' => 'Loved it.',
                ],
            ],
        ]);
    }
}
