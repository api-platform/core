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

namespace ApiPlatform\Tests\Functional\JsonApi;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiRequiredFieldsResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class InputDtoTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [JsonApiInputResource::class, JsonApiRequiredFieldsResource::class];
    }

    /**
     * Without the JSON:API ItemNormalizer guarding against double unwrapping,
     * the second pass reads $data['data']['attributes'] from already-flat data
     * and gets null, which nulls every DTO property.
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
