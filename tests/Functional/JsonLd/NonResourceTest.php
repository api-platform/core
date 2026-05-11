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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\DateTimeOnlyResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\GenIdFalseProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NonRelationResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NonResourceContainer;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PlainObjectResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NonResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            NonResourceContainer::class,
            NonRelationResource::class,
            PlainObjectResource::class,
            GenIdFalseProperty::class,
            DateTimeOnlyResource::class,
        ];
    }

    public function testNonResourceObjectHasGenidAndType(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_non_resource_containers/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/JsonLdNonResourceContainer',
            '@id' => '/jsonld_non_resource_containers/1',
            '@type' => 'JsonLdNonResourceContainer',
            'id' => '1',
            'nested' => [
                '@id' => '/jsonld_non_resource_containers/1-nested',
                '@type' => 'JsonLdNonResourceContainer',
                'id' => '1-nested',
                'notAResource' => [
                    '@type' => 'NonResourceClass',
                    'foo' => 'f2',
                    'bar' => 'b2',
                ],
            ],
            'notAResource' => [
                '@type' => 'NonResourceClass',
                'foo' => 'f1',
                'bar' => 'b1',
            ],
        ]);
        $body = $response->toArray();
        $this->assertArrayHasKey('@id', $body['notAResource']);
        $this->assertStringStartsWith('/.well-known/genid/', $body['notAResource']['@id']);
    }

    public function testCreateResourceWithNonResourceRelation(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_non_relation_resources', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['relation' => ['foo' => 'test']],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/JsonLdNonRelationResource',
            '@id' => '/jsonld_non_relation_resources/1',
            '@type' => 'JsonLdNonRelationResource',
            'relation' => [
                '@type' => 'NonRelationPayload',
                'foo' => 'test',
            ],
            'id' => 1,
        ]);
    }

    public function testCreateResourceWithStdClass(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_plain_object_resources', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'content' => '{"emptyObject":{},"showCaption":false,"alternativeContent":false,"blockLayout":"default"}',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/jsonld_plain_object_resources/1', $body['@id']);
        $this->assertSame('JsonLdPlainObjectResource', $body['@type']);
        $this->assertSame([], $body['data']['emptyObject']);
        $this->assertFalse($body['data']['showCaption']);
        $this->assertFalse($body['data']['alternativeContent']);
        $this->assertSame('default', $body['data']['blockLayout']);
    }

    public function testGenIdFalsePropertyOmitsAtId(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_genid_false_properties/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayNotHasKey('@id', $body['totalPrice']);
    }

    public function testResourceWithDateTimeProperty(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_datetime_resources/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertArrayHasKey('start', $body);
        $this->assertNotEmpty($body['start']);
    }

    public function testSparseFieldsetOnNonResourceObject(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/jsonld_non_resource_containers/1?properties[]=id&properties[nested][notAResource][]=foo&properties[notAResource][]=bar',
            ['headers' => ['Accept' => 'application/ld+json']],
        );
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('1', $body['id']);
        $this->assertSame('f2', $body['nested']['notAResource']['foo']);
        $this->assertSame('b1', $body['notAResource']['bar']);
        $this->assertArrayNotHasKey('bar', $body['nested']['notAResource']);
        $this->assertArrayNotHasKey('foo', $body['notAResource']);
    }
}
