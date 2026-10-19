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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\ClientGeneratedId;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi\StrictTypeResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class RequestDocumentValidationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ClientGeneratedId::class, StrictTypeResource::class];
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function malformedDocuments(): iterable
    {
        yield 'body is a string' => ['Y'];
        yield 'body is a number' => [42];
        yield 'no data member' => [['name' => 'A']];
        yield 'data is not an object' => [['data' => 'x']];
        yield 'attributes is a list' => [['data' => ['type' => 'JsonApiClientGeneratedId', 'attributes' => ['x']]]];
        yield 'attributes is null' => [['data' => ['type' => 'JsonApiClientGeneratedId', 'attributes' => null]]];
        yield 'relationships is null' => [['data' => ['type' => 'JsonApiClientGeneratedId', 'relationships' => null]]];
        yield 'relationships is a string' => [['data' => ['type' => 'JsonApiClientGeneratedId', 'relationships' => 'x']]];
    }

    #[DataProvider('malformedDocuments')]
    public function testMalformedDocumentIsRejected(mixed $document): void
    {
        self::createClient()->request('POST', '/jsonapi_client_generated_ids', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => $document,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testDocumentWithoutTypeIsStillAccepted(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_client_generated_ids', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => ['data' => ['attributes' => ['name' => 'A']]],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('A', $response->toArray()['data']['attributes']['name']);
    }

    public function testValidDocumentIsAccepted(): void
    {
        $response = self::createClient()->request('POST', '/jsonapi_client_generated_ids', [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => ['data' => ['type' => 'JsonApiClientGeneratedId', 'attributes' => ['name' => 'A']]],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('A', $response->toArray()['data']['attributes']['name']);
    }

    /**
     * @param array<string, mixed> $document
     */
    #[DataProvider('strictTypeDocuments')]
    public function testStrictResourceType(string $uri, array $document, int $expectedStatus): void
    {
        self::createClient()->request('POST', $uri, [
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ],
            'json' => $document,
        ]);

        $this->assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, int}>
     */
    public static function strictTypeDocuments(): iterable
    {
        yield 'strict, missing type' => ['/jsonapi_strict_types', ['data' => ['attributes' => ['name' => 'A']]], 400];
        yield 'strict, non-string type' => ['/jsonapi_strict_types', ['data' => ['type' => 1]], 400];
        yield 'strict, wrong type' => ['/jsonapi_strict_types', ['data' => ['type' => 'Author']], 409];
        yield 'strict, correct type' => ['/jsonapi_strict_types', ['data' => ['type' => 'JsonApiStrictType']], 201];
        yield 'strict, operation shortName' => ['/jsonapi_strict_type_aliases', ['data' => ['type' => 'JsonApiStrictTypeAlias']], 201];
        yield 'strict, emitted type' => ['/jsonapi_strict_type_aliases', ['data' => ['type' => 'JsonApiStrictType']], 201];
        yield 'strict, neither' => ['/jsonapi_strict_type_aliases', ['data' => ['type' => 'Author']], 409];
        yield 'lenient, missing type' => ['/jsonapi_lenient_types', ['data' => ['attributes' => ['name' => 'A']]], 201];
        yield 'lenient, wrong type' => ['/jsonapi_lenient_types', ['data' => ['type' => 'Author']], 201];
    }
}
