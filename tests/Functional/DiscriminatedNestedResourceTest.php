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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedPersianCat;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedPet;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedPetDog;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedPetStore;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedSiameseCat;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Polymorphic resources whose discriminator map contains a subtype declaring its own discriminator map.
 */
final class DiscriminatedNestedResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DiscriminatedPet::class];
    }

    protected function setUp(): void
    {
        $persian = new DiscriminatedPersianCat();
        $persian->id = 1;
        $persian->name = 'Garfield';
        $persian->lives = 7;
        $persian->furLength = 'long';

        $dog = new DiscriminatedPetDog();
        $dog->id = 2;
        $dog->name = 'Odie';

        DiscriminatedPetStore::$pets = [1 => $persian, 2 => $dog];
    }

    protected function tearDown(): void
    {
        DiscriminatedPetStore::$pets = [];

        parent::tearDown();
    }

    public function testGetItemExposesTheTypeOfEveryMap(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_pets/1');

        $this->assertResponseIsSuccessful();
        $this->assertEquals([
            '@context' => '/contexts/DiscriminatedPet',
            '@id' => '/discriminated_pets/1',
            '@type' => 'DiscriminatedPet',
            'kind' => 'cat',
            'breed' => 'persian',
            'id' => 1,
            'name' => 'Garfield',
            'lives' => 7,
            'furLength' => 'long',
        ], $response->toArray());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function formatProvider(): iterable
    {
        yield 'json' => ['application/json'];
        yield 'jsonapi' => ['application/vnd.api+json'];
        yield 'hal' => ['application/hal+json'];
    }

    #[DataProvider('formatProvider')]
    public function testGetItemInOtherFormats(string $accept): void
    {
        $json = self::createClient()->request('GET', '/discriminated_pets/1', ['headers' => ['Accept' => $accept]])->toArray();

        $this->assertResponseIsSuccessful();
        $attributes = $json['data']['attributes'] ?? $json;
        $this->assertSame('cat', $attributes['kind']);
        $this->assertSame('persian', $attributes['breed']);
        $this->assertSame('long', $attributes['furLength']);
    }

    public function testGetCollection(): void
    {
        $members = self::createClient()->request('GET', '/discriminated_pets')->toArray()['hydra:member'];

        $this->assertResponseIsSuccessful();
        $this->assertSame(['cat', 'dog'], array_column($members, 'kind'));
        $this->assertSame('persian', $members[0]['breed']);
        $this->assertArrayNotHasKey('breed', $members[1]);
        $this->assertTrue($members[1]['goodBoy']);
    }

    public function testPostCreatesTheMostSpecificSubtype(): void
    {
        self::createClient()->request('POST', '/discriminated_pets', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'cat', 'breed' => 'siamese', 'name' => 'Tom', 'lives' => 3, 'eyeColor' => 'blue'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['kind' => 'cat', 'breed' => 'siamese', 'name' => 'Tom', 'lives' => 3, 'eyeColor' => 'blue']);
        $this->assertInstanceOf(DiscriminatedSiameseCat::class, DiscriminatedPetStore::$pets[3]);
        $this->assertSame('blue', DiscriminatedPetStore::$pets[3]->eyeColor);
    }

    public function testPostWithoutNestedTypeIsRejected(): void
    {
        self::createClient()->request('POST', '/discriminated_pets', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'cat', 'name' => 'Tom'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayNotHasKey(3, DiscriminatedPetStore::$pets);
    }

    public function testPostRejectsPropertiesOfAnotherSubtype(): void
    {
        self::createClient()->request('POST', '/discriminated_pets', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'cat', 'breed' => 'siamese', 'name' => 'Tom', 'furLength' => 'short'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPatchUpdatesNestedSubtypeProperties(): void
    {
        self::createClient()->request('PATCH', '/discriminated_pets/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'cat', 'breed' => 'persian', 'furLength' => 'short'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'cat', 'breed' => 'persian', 'furLength' => 'short']);
        $this->assertInstanceOf(DiscriminatedPersianCat::class, DiscriminatedPetStore::$pets[1]);
    }

    public function testPatchCannotChangeTheNestedType(): void
    {
        self::createClient()->request('PATCH', '/discriminated_pets/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['breed' => 'siamese'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertInstanceOf(DiscriminatedPersianCat::class, DiscriminatedPetStore::$pets[1]);
    }

    public function testOpenApiDocumentsNestedSubtypes(): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();
        $schemas = $json['components']['schemas'];

        $cat = $schemas['DiscriminatedPet.DiscriminatedPetCat'];
        $this->assertSame('breed', $cat['discriminator']['propertyName']);
        $this->assertCount(2, $cat['oneOf']);

        // A nested subtype documents the type properties of every map it belongs to.
        $persian = $schemas['DiscriminatedPet.DiscriminatedPersianCat'];
        $this->assertSame(['type' => 'string', 'enum' => ['cat']], $persian['properties']['kind']);
        $this->assertSame(['type' => 'string', 'enum' => ['persian']], $persian['properties']['breed']);
        $this->assertContains('kind', $persian['required']);
        $this->assertContains('breed', $persian['required']);
        $this->assertFalse($persian['additionalProperties']);

        // "additionalProperties" ignores "oneOf" branches: it must only restrict the leaf definitions.
        $this->assertArrayNotHasKey('additionalProperties', $schemas['DiscriminatedPet']);
        $this->assertArrayNotHasKey('additionalProperties', $cat);
        $this->assertFalse($schemas['DiscriminatedPet.DiscriminatedPetDog']['additionalProperties']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function inputPayloadProvider(): iterable
    {
        yield 'dog' => [['kind' => 'dog', 'name' => 'Odie', 'goodBoy' => true], true];
        yield 'nested cat' => [['kind' => 'cat', 'breed' => 'persian', 'name' => 'Garfield', 'lives' => 7, 'furLength' => 'long'], true];
        yield 'property of a sibling subtype' => [['kind' => 'cat', 'breed' => 'persian', 'name' => 'Garfield', 'eyeColor' => 'blue'], false];
        yield 'unknown property' => [['kind' => 'dog', 'name' => 'Odie', 'unknown' => true], false];
        yield 'missing nested type' => [['kind' => 'cat', 'name' => 'Garfield'], false];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('inputPayloadProvider')]
    public function testInputSchemaValidatesPayloadsWhenExtraAttributesAreNotAllowed(array $payload, bool $valid): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();

        $schema = json_decode(json_encode([
            '$ref' => $json['paths']['/discriminated_pets']['post']['requestBody']['content']['application/ld+json']['schema']['$ref'],
            'components' => ['schemas' => $json['components']['schemas']],
        ]));
        $data = json_decode(json_encode($payload));

        $validator = new Validator();
        $validator->validate($data, $schema);

        $this->assertSame($valid, $validator->isValid(), json_encode($validator->getErrors()));
    }
}
