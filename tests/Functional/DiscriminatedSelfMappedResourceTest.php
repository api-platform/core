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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedGadget;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedGadgetStore;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource\DiscriminatedPhone;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Concrete polymorphic resource declaring itself in its own discriminator map.
 */
final class DiscriminatedSelfMappedResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DiscriminatedGadget::class];
    }

    protected function setUp(): void
    {
        $gadget = new DiscriminatedGadget();
        $gadget->id = 1;
        $gadget->name = 'Spinner';

        $phone = new DiscriminatedPhone();
        $phone->id = 2;
        $phone->name = 'Pocket';
        $phone->number = '555-0100';

        DiscriminatedGadgetStore::$gadgets = [1 => $gadget, 2 => $phone];
    }

    protected function tearDown(): void
    {
        DiscriminatedGadgetStore::$gadgets = [];

        parent::tearDown();
    }

    public function testGetBaseItemExposesItsOwnType(): void
    {
        $response = self::createClient()->request('GET', '/discriminated_gadgets/1');

        $this->assertResponseIsSuccessful();
        $this->assertEquals([
            '@context' => '/contexts/DiscriminatedGadget',
            '@id' => '/discriminated_gadgets/1',
            '@type' => 'DiscriminatedGadget',
            'kind' => 'gadget',
            'id' => 1,
            'name' => 'Spinner',
        ], $response->toArray());
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function jsonApiItemProvider(): iterable
    {
        yield 'base type' => [1, 'gadget'];
        yield 'subtype' => [2, 'phone'];
    }

    #[DataProvider('jsonApiItemProvider')]
    public function testGetItemInJsonApiMatchesItsJsonSchema(int $id, string $kind): void
    {
        $json = self::createClient()->request('GET', '/discriminated_gadgets/'.$id, ['headers' => ['Accept' => 'application/vnd.api+json']])->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame($kind, $json['data']['attributes']['kind']);
        $this->assertMatchesResourceItemJsonSchema(DiscriminatedGadget::class, format: 'jsonapi');
    }

    public function testGetCollectionExposesTheTypeOfEveryMember(): void
    {
        $members = self::createClient()->request('GET', '/discriminated_gadgets')->toArray()['hydra:member'];

        $this->assertResponseIsSuccessful();
        $this->assertSame(['gadget', 'phone'], array_column($members, 'kind'));
        $this->assertArrayNotHasKey('number', $members[0]);
        $this->assertSame('555-0100', $members[1]['number']);
    }

    public function testPostBaseTypeWithTypeProperty(): void
    {
        self::createClient()->request('POST', '/discriminated_gadgets', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'gadget', 'name' => 'Yo-yo'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['kind' => 'gadget', 'name' => 'Yo-yo']);
        $this->assertSame(DiscriminatedGadget::class, DiscriminatedGadgetStore::$gadgets[3]::class);
    }

    public function testPostSubtype(): void
    {
        self::createClient()->request('POST', '/discriminated_gadgets', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'phone', 'name' => 'Brick', 'number' => '555-0199'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['kind' => 'phone', 'number' => '555-0199']);
        $this->assertInstanceOf(DiscriminatedPhone::class, DiscriminatedGadgetStore::$gadgets[3]);
    }

    public function testPostBaseTypeRejectsSubtypeProperties(): void
    {
        self::createClient()->request('POST', '/discriminated_gadgets', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['kind' => 'gadget', 'name' => 'Yo-yo', 'number' => '555-0199'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPatchBaseItemWithItsType(): void
    {
        self::createClient()->request('PATCH', '/discriminated_gadgets/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'gadget', 'name' => 'Fidget'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['kind' => 'gadget', 'name' => 'Fidget']);
    }

    public function testPatchCannotChangeTheBaseItemType(): void
    {
        self::createClient()->request('PATCH', '/discriminated_gadgets/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['kind' => 'phone'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(DiscriminatedGadget::class, DiscriminatedGadgetStore::$gadgets[1]::class);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function inputPayloadProvider(): iterable
    {
        yield 'base type' => [['kind' => 'gadget', 'name' => 'Yo-yo'], true];
        yield 'subtype' => [['kind' => 'phone', 'name' => 'Brick', 'number' => '555-0199'], true];
        yield 'unknown property' => [['kind' => 'gadget', 'name' => 'Yo-yo', 'unknown' => true], false];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('inputPayloadProvider')]
    public function testInputSchemaMatchesTheAcceptedPayloads(array $payload, bool $valid): void
    {
        $json = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();

        $schema = json_decode(json_encode([
            '$ref' => $json['paths']['/discriminated_gadgets']['post']['requestBody']['content']['application/ld+json']['schema']['$ref'],
            'components' => ['schemas' => $json['components']['schemas']],
        ]));
        $data = json_decode(json_encode($payload));

        $validator = new Validator();
        $validator->validate($data, $schema);

        $this->assertSame($valid, $validator->isValid(), json_encode($validator->getErrors()));
    }
}
