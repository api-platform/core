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
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ErrorObjectMembersTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [ErrorProblem::class];
    }

    public function testValidationErrorHasStatusAndTitle(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_validation_problem', [
            'headers' => ['accept' => 'application/vnd.api+json', 'content-type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'JsonApiErrorProblem', 'attributes' => new \stdClass()]],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $error = $response->toArray(false)['errors'][0];
        $this->assertSame('422', $error['status'] ?? null);
        $this->assertArrayHasKey('title', $error);
    }

    public function testValidationErrorPointerIsAJsonPointer(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_validation_problem', [
            'headers' => ['accept' => 'application/vnd.api+json', 'content-type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'JsonApiErrorProblem', 'attributes' => new \stdClass()]],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('/data/attributes/name', $response->toArray(false)['errors'][0]['source']['pointer']);
    }

    public function testValidationErrorStatusFollowsTheResponseStatus(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_validation_problem_bad_request', [
            'headers' => ['accept' => 'application/vnd.api+json', 'content-type' => 'application/vnd.api+json'],
            'json' => ['data' => ['type' => 'JsonApiErrorProblem', 'attributes' => new \stdClass()]],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('400', $response->toArray(false)['errors'][0]['status'] ?? null);
    }
}
