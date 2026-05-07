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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\CustomInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\CustomOutputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\DummyCollectionDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\DummyFooCollectionDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\DummyIdCollectionDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\InputOutputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NoInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PostNoOutputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UserResource;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputOutputDtoTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            CustomInputResource::class,
            CustomOutputResource::class,
            InputOutputResource::class,
            NoInputResource::class,
            PostNoOutputResource::class,
            DummyCollectionDto::class,
            DummyFooCollectionDto::class,
            DummyIdCollectionDto::class,
            UserResource::class,
        ];
    }

    public function testCreateResourceWithCustomInput(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_custom_inputs', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['foo' => 'test', 'bar' => 1],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $this->assertJsonContains([
            '@context' => '/contexts/JsonLdCustomInput',
            '@id' => '/jsonld_custom_inputs/1',
            '@type' => 'JsonLdCustomInput',
            'lorem' => 'test',
            'ipsum' => '1',
            'id' => 1,
        ]);
    }

    public function testCustomInputRejectsBadType(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_custom_inputs', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['foo' => 'test', 'bar' => 'not-an-int'],
        ]);
        $this->assertResponseStatusCodeSame(400);
        $body = $response->toArray(false);
        $this->assertSame('The input data is misformatted.', $body['detail']);
    }

    public function testItemWithCustomOutput(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_custom_outputs/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('CustomOutputDto', $body['@type']);
        $this->assertSame('test', $body['foo']);
        $this->assertSame(1, $body['bar']);
        $this->assertArrayHasKey('@context', $body);
    }

    public function testCollectionWithCustomOutput(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_custom_outputs', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdCustomOutput', $body['@context']);
        $this->assertSame('/jsonld_custom_outputs', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame(2, $body['hydra:totalItems']);
        $this->assertCount(2, $body['hydra:member']);
        $this->assertSame('CustomOutputDto', $body['hydra:member'][0]['@type']);
    }

    public function testPostWithoutOutputReturns204(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_post_no_output', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['lorem' => 'a', 'ipsum' => 'b'],
        ]);
        $this->assertResponseStatusCodeSame(204);
        $this->assertEmpty($response->getContent());
    }

    public function testInputOutputCycle(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_input_outputs', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['foo' => 'test', 'bar' => 1],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('InputOutputDto', $body['@type']);
        $this->assertSame(1, $body['id']);
        $this->assertSame(1, $body['baz']);
        $this->assertSame('test', $body['bat']);
        $this->assertSame([], $body['relatedDummies']);

        $response = self::createClient()->request('PUT', '/jsonld_input_outputs/1', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['foo' => 'test', 'bar' => 2],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('InputOutputDto', $body['@type']);
        $this->assertSame(1, $body['id']);
        $this->assertSame(2, $body['baz']);
        $this->assertSame('test', $body['bat']);
    }

    public function testCreateNoInputResource(): void
    {
        if ($_SERVER['USE_SYMFONY_LISTENERS'] ?? false) {
            $this->markTestSkipped('PlaceholderAction cannot resolve $data when input:false in event-listener mode.');
        }

        $response = self::createClient()->request('POST', '/jsonld_no_inputs', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $body = $response->toArray();
        $this->assertSame('JsonLdNoInput', $body['@type']);
        $this->assertSame(1, $body['id']);
        $this->assertSame(1, $body['baz']);
        $this->assertSame('test', $body['bat']);
    }

    public function testUpdateNoInputResource(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_no_inputs/1/double_bat', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('JsonLdNoInput', $body['@type']);
        $this->assertSame('testtest', $body['bat']);
    }

    public function testCollectionWithCustomOutputAndNoIdentifierUsesGenid(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_dummy_collection_dtos', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdDummyCollectionDto', $body['@context']);
        $this->assertSame('/jsonld_dummy_collection_dtos', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertCount(2, $body['hydra:member']);
        $this->assertSame(2, $body['hydra:totalItems']);
        foreach ($body['hydra:member'] as $member) {
            $this->assertStringStartsWith('/.well-known/genid/', $member['@id']);
            $this->assertSame('DummyCollectionDtoOutput', $member['@type']);
            $this->assertSame('foo', $member['foo']);
            $this->assertIsInt($member['bar']);
        }
    }

    public function testCollectionWithItemUriTemplateUsesIt(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_dummy_foo_collection_dtos', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdDummyFooCollectionDto', $body['@context']);
        $this->assertSame('/jsonld_dummy_foo_collection_dtos', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertCount(2, $body['hydra:member']);
        foreach ($body['hydra:member'] as $member) {
            $this->assertStringContainsString('/jsonld_dummy_foos/bar', $member['@id']);
            $this->assertSame('JsonLdDummyFooCollectionDto', $member['@type']);
        }
    }

    public function testCollectionWithCustomOutputResourceWithIdentifierUsesGenid(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_dummy_id_collection_dtos', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdDummyIdCollectionDto', $body['@context']);
        $this->assertCount(2, $body['hydra:member']);
        foreach ($body['hydra:member'] as $member) {
            $this->assertStringStartsWith('/.well-known/genid/', $member['@id']);
            $this->assertSame('DummyIdCollectionDtoOutput', $member['@type']);
            $this->assertArrayHasKey('id', $member);
            $this->assertArrayHasKey('foo', $member);
            $this->assertArrayHasKey('bar', $member);
        }
    }

    public function testResetPasswordViaInputDto(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('POST', '/user-reset-password', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['email' => 'user@example.com'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $body = $response->toArray();
        $this->assertSame('user@example.com', $body['email']);
    }

    public function testResetPasswordWithInvalidEmailReturns422(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('POST', '/user-reset-password', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['email' => 'this is not an email'],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }
}
