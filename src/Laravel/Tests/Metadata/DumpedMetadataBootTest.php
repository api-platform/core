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

namespace ApiPlatform\Laravel\Tests\Metadata;

use ApiPlatform\Laravel\Metadata\DumpedResourceCollectionMetadataFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DumpedMetadataBootTest extends TestCase
{
    use WithWorkbench;

    private const RESOURCE_CLASS = 'App\\NotAnEloquentModel';

    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'apip_boot_dump_').'.meta';

        $dumped = new ResourceMetadataCollection(self::RESOURCE_CLASS, [new ApiResource(shortName: 'FromDump')]);
        file_put_contents($this->dumpPath, serialize([self::RESOURCE_CLASS => $dumped]));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', false);
        $app['config']->set('api-platform.metadata_dump', $this->dumpPath);
    }

    public function testItServesMetadataFromTheDumpWithoutHittingTheDatabase(): void
    {
        $factory = $this->app->make(ResourceMetadataCollectionFactoryInterface::class);

        $this->assertInstanceOf(DumpedResourceCollectionMetadataFactory::class, $factory);

        // The class is not a real Eloquent model; if the dump were not consulted the inner
        // factory chain would try to introspect a non-existent model/table.
        $metadata = $factory->create(self::RESOURCE_CLASS);

        $this->assertCount(1, $metadata);
        $this->assertSame('FromDump', $metadata[0]->getShortName());
    }

    public function testItIsNotWrappedWhenDebugIsEnabled(): void
    {
        $this->app['config']->set('app.debug', true);
        $this->app->forgetInstance(ResourceMetadataCollectionFactoryInterface::class);

        $factory = $this->app->make(ResourceMetadataCollectionFactoryInterface::class);

        $this->assertNotInstanceOf(DumpedResourceCollectionMetadataFactory::class, $factory);
    }
}
