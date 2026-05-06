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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\InputOutputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NoInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PostNoOutputResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputOutputDtoTest extends ApiTestCase
{
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
}
