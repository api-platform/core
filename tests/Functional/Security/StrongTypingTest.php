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

namespace ApiPlatform\Tests\Functional\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class StrongTypingTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Dummy::class, RelatedDummy::class, RelatedOwnedDummy::class, RelatedOwningDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([Dummy::class]);
    }

    public function testIgnoreUnsupportedAttributes(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'Not existing', 'unsupported' => true]),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/Dummy',
            '@id' => '/dummies/1',
            '@type' => 'Dummy',
            'description' => null,
            'dummy' => null,
            'dummyBoolean' => null,
            'dummyDate' => null,
            'dummyFloat' => null,
            'dummyPrice' => null,
            'relatedDummy' => null,
            'relatedDummies' => [],
            'jsonData' => [],
            'arrayData' => [],
            'name_converted' => null,
            'relatedOwnedDummy' => null,
            'relatedOwningDummy' => null,
            'id' => 1,
            'name' => 'Not existing',
            'alias' => null,
            'foo' => null,
        ]);
    }

    public function testNullValueForRequiredStringTriggersTypeError(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => null]),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'detail' => 'The type of the "name" attribute must be "string", "NULL" given.',
        ]);
    }

    public function testStringInsteadOfIriOnRelationTriggersInvalidIri(): void
    {
        $response = self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'Foo', 'relatedDummy' => '1']),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'detail' => 'Invalid IRI "1".',
        ]);
        $this->assertArrayHasKey('trace', $response->toArray(false));
    }

    public function testInvalidDateStringIsRejected(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'Invalid date', 'dummyDate' => 'Invalid']),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testDateWithUnexpectedFormatIsRejected(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'Invalid date format', 'dummyDateWithFormat' => '2020-01-01T00:00:00+00:00']),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
    }

    public function testStringInsteadOfArrayOnCollectionRelationTriggersTypeError(): void
    {
        $response = self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'Invalid', 'relatedDummies' => 'hello']),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'detail' => 'The type of the "relatedDummies" attribute must be "array", "string" given.',
        ]);
        $this->assertArrayHasKey('trace', $response->toArray(false));
    }

    public function testAssociativeObjectInsteadOfListOnCollectionTriggersKeyTypeError(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'Invalid', 'relatedDummies' => ['a' => new \stdClass(), 'b' => new \stdClass()]]),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'detail' => 'The type of the key "a" must be "int", "string" given.',
        ]);
    }

    public function testIntegerInsteadOfStringScalarTriggersTypeError(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 42]),
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'detail' => 'The type of the "name" attribute must be "string", "integer" given.',
        ]);
    }

    public function testIntegerIsAcceptedForFloatProperty(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
                'body' => json_encode(['name' => 'foo', 'dummyFloat' => 42]),
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }
}
