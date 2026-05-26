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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PostWithUriVariables;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\IncompleteUriVariableConfigured;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class AttributeResourceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [AttributeResource::class, AttributeResources::class, IncompleteUriVariableConfigured::class, PostWithUriVariables::class, Dummy::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB() || $this->isMysql()) {
            $this->markTestSkipped();
        }
    }

    public function testGetAttributeResourcesCollection(): void
    {
        self::createClient()->request('GET', '/attribute_resources', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/AttributeResources',
            '@id' => '/attribute_resources',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                ['@id' => '/attribute_resources/1', '@type' => 'AttributeResource', 'identifier' => 1, 'name' => 'Foo'],
                ['@id' => '/attribute_resources/2', '@type' => 'AttributeResource', 'identifier' => 2, 'name' => 'Bar'],
            ],
        ]);
    }

    public function testGetAttributeResourceItem(): void
    {
        self::createClient()->request('GET', '/attribute_resources/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/AttributeResource',
            '@id' => '/attribute_resources/1',
            '@type' => 'AttributeResource',
            'identifier' => 1,
            'name' => 'Foo',
        ]);
    }

    public function testAliasedResourceRedirectsAndShowsTarget(): void
    {
        self::createClient()->request('GET', '/dummy/1/attribute_resources/2', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(301);
        $this->assertResponseHeaderSame('Location', '/attribute_resources/2');
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/AttributeResource',
            '@id' => '/attribute_resources/2',
            '@type' => 'AttributeResource',
            'identifier' => 2,
            'dummy' => '/dummies/1',
            'name' => 'Foo',
        ]);
    }

    public function testPatchAliasedResource(): void
    {
        self::createClient()->request('PATCH', '/dummy/1/attribute_resources/2', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Patched'],
        ]);

        $this->assertResponseStatusCodeSame(301);
        $this->assertResponseHeaderSame('Location', '/attribute_resources/2');
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/AttributeResource',
            '@id' => '/attribute_resources/2',
            '@type' => 'AttributeResource',
            'identifier' => 2,
            'dummy' => '/dummies/1',
            'name' => 'Patched',
        ]);
    }

    public function testIncompleteUriVariableConfigurationProducesProblem(): void
    {
        $response = self::createClient()->request('GET', '/photos/1/resize/300/100');

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $linkHeader = $response->getHeaders(false)['link'][0] ?? '';
        $this->assertStringContainsString('<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"', $linkHeader);
        $this->assertJsonContains(['detail' => 'Unable to generate an IRI for the item of type "ApiPlatform\\Tests\\Fixtures\\TestBundle\\Entity\\IncompleteUriVariableConfigured"']);
    }

    public function testPostWithUriVariablesAndNoProvider(): void
    {
        self::createClient()->request('POST', '/post_with_uri_variables_and_no_provider/{id}', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testProviderThrowsValidationException(): void
    {
        self::createClient()->request('POST', '/post_with_uri_variables/{id}', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);

        $this->assertResponseStatusCodeSame(422);
    }
}
