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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\ErrorProblem;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApiErrorTestResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json');
        $this->assertJsonContains([
            'errors' => [
                [
                    'status' => '400',
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
                    'status' => '422',
                    'title' => 'Validation Failed',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/name'],
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
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json');
        $body = $response->toArray(false);
        $this->assertSame('An error occurred', $body['errors'][0]['title']);
        $this->assertSame('400', $body['errors'][0]['status']);
        $this->assertArrayHasKey('detail', $body['errors'][0]);
        $this->assertArrayNotHasKey('type', $body['errors'][0]);
        $this->assertArrayHasKey('type', $body['errors'][0]['links']);
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
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json');
        $body = $response->toArray(false);
        $this->assertSame('An error occurred', $body['errors'][0]['title']);
        $this->assertSame('404', $body['errors'][0]['status']);
        $this->assertArrayHasKey('detail', $body['errors'][0]);
        $this->assertArrayNotHasKey('type', $body['errors'][0]);
        $this->assertArrayHasKey('type', $body['errors'][0]['links']);
    }

    public function testRouterNotFoundRendersJsonApiErrorObject(): void
    {
        $response = $this->requestWithSymfonyErrorHandling('GET', '/does_not_exist');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json');
        $error = $response->toArray(false)['errors'][0];
        $this->assertSame('/errors/404', $error['id']);
        $this->assertSame('An error occurred', $error['title']);
        $this->assertStringContainsString('No route found', $error['detail']);
        $this->assertSame('404', $error['status']);
        $this->assertSame('404', $error['code']);
        $this->assertArrayNotHasKey('type', $error);
        $this->assertSame(['type' => '/errors/404'], $error['links']);
    }

    public function testMethodNotAllowedRendersJsonApiErrorObject(): void
    {
        $response = $this->requestWithSymfonyErrorHandling('DELETE', '/jsonapi_error_test/existing');

        $this->assertResponseStatusCodeSame(405);
        $this->assertResponseHeaderSame('content-type', 'application/vnd.api+json');
        $error = $response->toArray(false)['errors'][0];
        $this->assertSame('/errors/405', $error['id']);
        $this->assertSame('An error occurred', $error['title']);
        $this->assertStringContainsString('Method Not Allowed', $error['detail']);
        $this->assertSame('405', $error['status']);
        $this->assertSame('405', $error['code']);
        $this->assertArrayNotHasKey('type', $error);
        $this->assertSame(['type' => '/errors/405'], $error['links']);
    }

    private function requestWithSymfonyErrorHandling(string $method, string $uri): ResponseInterface
    {
        $client = self::createClient();
        $client->disableReboot();
        static::getContainer()->get('api_platform.listener.exception')->handleSymfonyErrors = false;

        return $client->request($method, $uri, [
            'headers' => ['accept' => 'application/vnd.api+json'],
        ]);
    }
}
