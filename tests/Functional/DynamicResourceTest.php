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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\DynamicResource\DynamicRecord;
use ApiPlatform\Tests\Fixtures\TestBundle\DynamicResource\DynamicResourceHost;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DynamicResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DynamicResourceHost::class];
    }

    public function testResourceMetadata(): void
    {
        self::bootKernel();
        $resourceMetadataCollection = static::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class)->create(DynamicRecord::class);

        foreach (['DynamicFaq' => '/dynamic/faq/{id}', 'DynamicNews' => '/dynamic/news/{id}'] as $shortName => $uriTemplate) {
            $found = null;
            foreach ($resourceMetadataCollection as $resource) {
                if ($shortName === $resource->getShortName()) {
                    $found = $resource->getOperations()->getIterator()->current();
                }
            }

            $this->assertInstanceOf(HttpOperation::class, $found);
            $this->assertSame(DynamicRecord::class, $found->getClass());
            $this->assertSame($shortName, $found->getShortName());
            $this->assertSame($uriTemplate, $found->getUriTemplate());
        }
    }

    public function testGetResourceFromIri(): void
    {
        self::bootKernel();
        $iriConverter = static::getContainer()->get(IriConverterInterface::class);

        $resource = $iriConverter->getResourceFromIri('/dynamic/faq/1');
        $this->assertInstanceOf(DynamicRecord::class, $resource);
        $this->assertSame('DynamicFaq', $resource->getResource());
        $this->assertSame('What is API Platform?', $resource->__get('question'));

        $resource = $iriConverter->getResourceFromIri('/dynamic/news/1');
        $this->assertInstanceOf(DynamicRecord::class, $resource);
        $this->assertSame('DynamicNews', $resource->getResource());
        $this->assertSame('API Platform 5.0', $resource->__get('title'));
    }

    public function testGetItem(): void
    {
        $client = self::createClient();

        $client->request('GET', '/dynamic/faq/1', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@id' => '/dynamic/faq/1', '@type' => 'DynamicFaq', 'question' => 'What is API Platform?']);

        $client->request('GET', '/dynamic/news/1', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@id' => '/dynamic/news/1', '@type' => 'DynamicNews', 'title' => 'API Platform 5.0']);
    }

    public function testOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        $this->assertResponseIsSuccessful();
        $openApi = $response->toArray();
        $this->assertArrayHasKey('/dynamic/faq/{id}', $openApi['paths']);
        $this->assertArrayHasKey('/dynamic/faq', $openApi['paths']);
        $this->assertArrayHasKey('/dynamic/news/{id}', $openApi['paths']);
        $this->assertArrayHasKey('/dynamic/news', $openApi['paths']);
        $this->assertSame(['id', 'question', 'answer', 'related'], array_keys($openApi['components']['schemas']['DynamicFaq']['properties']));
        $this->assertSame(['id', 'title', 'body'], array_keys($openApi['components']['schemas']['DynamicNews']['properties']));
    }

    public function testNestedResourceOnSharedClass(): void
    {
        $response = self::createClient()->request('GET', '/dynamic/faq/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['related' => ['@id' => '/dynamic/news/1', '@type' => 'DynamicNews', 'title' => 'API Platform 5.0']]);
        $this->assertArrayNotHasKey('question', $response->toArray()['related']);
    }
}
