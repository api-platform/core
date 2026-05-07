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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\ErrorProblem;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiErrorTestResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class ErrorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [JsonApiErrorTestResource::class, ErrorProblem::class];
    }

    public function testErrorResourceRendersInJsonApiFormat(): void
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

    public function testValidationErrorRendersJsonApiPointer(): void
    {
        self::createClient()->request('POST', '/jsonapi_validation_problem', [
            'headers' => [
                'accept' => 'application/vnd.api+json',
                'content-type' => 'application/vnd.api+json',
            ],
            'json' => [
                'data' => [
                    'type' => 'JsonApiErrorProblem',
                    'attributes' => new \stdClass(),
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonEquals([
            'errors' => [
                [
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => 'data/attributes/name'],
                ],
            ],
        ]);
    }

    public function testRfc7807ErrorRendersJsonApiFormat(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_exception_problem', [
            'headers' => [
                'accept' => 'application/vnd.api+json',
                'content-type' => 'application/vnd.api+json',
            ],
            'json' => new \stdClass(),
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray(false);
        $this->assertSame('An error occurred', $body['errors'][0]['title']);
        $this->assertSame(400, $body['errors'][0]['status']);
        $this->assertArrayHasKey('detail', $body['errors'][0]);
        $this->assertArrayHasKey('type', $body['errors'][0]);
    }

    public function testNotFoundRouteRendersJsonApiFormat(): void
    {
        $response = self::createClient()->request('POST', '/does_not_exist', [
            'headers' => [
                'accept' => 'application/vnd.api+json',
                'content-type' => 'application/vnd.api+json',
            ],
            'json' => new \stdClass(),
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json; charset=utf-8');
        $body = $response->toArray(false);
        $this->assertSame('An error occurred', $body['errors'][0]['title']);
        $this->assertSame(404, $body['errors'][0]['status']);
        $this->assertArrayHasKey('detail', $body['errors'][0]);
        $this->assertArrayHasKey('type', $body['errors'][0]);
    }
}
