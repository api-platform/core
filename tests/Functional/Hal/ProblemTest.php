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

namespace ApiPlatform\Tests\Functional\Hal;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\ProblemRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal\ProblemResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ProblemTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [ProblemResource::class, ProblemRelation::class];
    }

    public function testValidationErrorIsReturnedAsProblemJson(): void
    {
        $response = self::createClient()->request('POST', '/hal_problems', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [],
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $body = $response->toArray(false);
        $this->assertSame('/validation_errors/c1051bb4-d103-4f74-8988-acbcafc7fdc3', $body['type']);
        $this->assertSame('An error occurred', $body['title']);
        $this->assertSame('name: This value should not be blank.', $body['detail']);
        $this->assertSame(422, $body['status']);
        $this->assertSame([
            [
                'propertyPath' => 'name',
                'message' => 'This value should not be blank.',
                'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
            ],
        ], $body['violations']);
    }

    public function testNestedRelationDocumentReturns400Problem(): void
    {
        $response = self::createClient()->request('POST', '/hal_problems', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'name' => 'Foo',
                'relatedDummy' => ['name' => 'bar'],
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $body = $response->toArray(false);
        $this->assertSame('/errors/400', $body['type']);
        $this->assertSame('An error occurred', $body['title']);
        $this->assertSame('Nested documents for attribute "relatedDummy" are not allowed. Use IRIs instead.', $body['detail']);
        $this->assertArrayHasKey('trace', $body);
    }
}
