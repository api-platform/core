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

/**
 * Resources identified by a name which is not a PHP class, see https://github.com/api-platform/core/issues/8791.
 */
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
        $resourceMetadataCollection = static::getContainer()->get(ResourceMetadataCollectionFactoryInterface::class)->create('DynamicFaq');

        $operation = $resourceMetadataCollection->getOperation();
        $this->assertInstanceOf(HttpOperation::class, $operation);
        $this->assertSame('DynamicFaq', $operation->getClass());
        $this->assertSame('DynamicFaq', $operation->getShortName());
        $this->assertSame('/dynamic/faq/{id}', $operation->getUriTemplate());
    }

    public function testGetResourceFromIri(): void
    {
        self::bootKernel();
        $iriConverter = static::getContainer()->get(IriConverterInterface::class);

        $resource = $iriConverter->getResourceFromIri('/dynamic/faq/1');
        $this->assertInstanceOf(DynamicRecord::class, $resource);
        $this->assertSame('DynamicFaq', $resource->getResource());
        $this->assertSame('What is API Platform?', $resource->__get('question'));
    }

    public function testOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        $this->assertResponseIsSuccessful();
        $openApi = $response->toArray();
        $this->assertArrayHasKey('/dynamic/faq/{id}', $openApi['paths']);
        $this->assertArrayHasKey('/dynamic/faq', $openApi['paths']);
        $this->assertSame(['id', 'question', 'answer'], array_keys($openApi['components']['schemas']['DynamicFaq']['properties']));
    }
}
