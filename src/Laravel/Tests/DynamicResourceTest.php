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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\DynamicResource\DynamicPropertyMetadataFactory;
use Workbench\App\DynamicResource\DynamicPropertyNameCollectionFactory;
use Workbench\App\DynamicResource\DynamicRecord;
use Workbench\App\DynamicResource\DynamicResourceMetadataCollectionFactory;
use Workbench\App\DynamicResource\DynamicResourceNameCollectionFactory;

/**
 * Resources identified by a name which is not a PHP class, see https://github.com/api-platform/core/issues/8791.
 */
class DynamicResourceTest extends TestCase
{
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app->extend(ResourceNameCollectionFactoryInterface::class, static fn (ResourceNameCollectionFactoryInterface $inner) => new DynamicResourceNameCollectionFactory($inner));
        // innermost resource metadata factory: the core factories still apply their defaults
        $app->extend('api_platform.metadata.resource.metadata_collection_factory.attributes', static fn (ResourceMetadataCollectionFactoryInterface $inner) => new DynamicResourceMetadataCollectionFactory($inner));
        $app->extend(PropertyNameCollectionFactoryInterface::class, static fn (PropertyNameCollectionFactoryInterface $inner) => new DynamicPropertyNameCollectionFactory($inner));
        $app->extend(PropertyMetadataFactoryInterface::class, static fn (PropertyMetadataFactoryInterface $inner) => new DynamicPropertyMetadataFactory($inner));
    }

    public function testResourceMetadata(): void
    {
        $operation = $this->app->make(ResourceMetadataCollectionFactoryInterface::class)->create('DynamicFaq')->getOperation();
        $this->assertInstanceOf(HttpOperation::class, $operation);

        $this->assertSame('DynamicFaq', $operation->getClass());
        $this->assertSame('DynamicFaq', $operation->getShortName());
        $this->assertSame('/dynamic/faq/{id}', $operation->getUriTemplate());
    }

    public function testGetResourceFromIri(): void
    {
        $resource = $this->app->make(IriConverterInterface::class)->getResourceFromIri('/api/dynamic/faq/1');

        $this->assertInstanceOf(DynamicRecord::class, $resource);
        $this->assertSame('DynamicFaq', $resource->getResource());
        $this->assertSame('What is API Platform?', $resource->__get('question'));
    }

    public function testOpenApi(): void
    {
        $response = $this->get('/api/docs', ['accept' => 'application/vnd.openapi+json']);

        $response->assertOk();
        $openApi = $response->json();
        $this->assertArrayHasKey('/api/dynamic/faq/{id}', $openApi['paths']);
        $this->assertArrayHasKey('/api/dynamic/faq', $openApi['paths']);
        $this->assertSame(['id', 'question', 'answer'], array_keys($openApi['components']['schemas']['DynamicFaq']['properties']));
    }
}
