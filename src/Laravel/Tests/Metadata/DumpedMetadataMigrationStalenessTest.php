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

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Metadata\MetadataDumpFingerprint;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Illuminate\Database\Events\MigrationsEnded;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;

class DumpedMetadataMigrationStalenessTest extends TestCase
{
    use WithWorkbench;

    private const RESOURCE_CLASS = 'App\\NotAnEloquentModel';

    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'apip_migrate_dump_').'.meta';
        $this->writeDump('placeholder-fingerprint');

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

    public function testItWarnsWhenTheSchemaFingerprintDiffersAfterMigration(): void
    {
        // The dump still carries the placeholder fingerprint, which cannot match the live schema.
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $this->app->instance(LoggerInterface::class, $logger);

        $this->app['events']->dispatch(new MigrationsEnded('up'));
    }

    public function testItDoesNotWarnWhenTheSchemaFingerprintMatches(): void
    {
        $current = MetadataDumpFingerprint::schema(
            $this->app->make(ResourceNameCollectionFactoryInterface::class)->create(),
            $this->app->make(ModelMetadata::class),
        );
        $this->writeDump($current);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $this->app->instance(LoggerInterface::class, $logger);

        $this->app['events']->dispatch(new MigrationsEnded('up'));
    }

    private function writeDump(string $schemaFingerprint): void
    {
        file_put_contents($this->dumpPath, serialize([
            'version' => MetadataDumpFingerprint::VERSION,
            'resources_fingerprint' => '',
            'schema_fingerprint' => $schemaFingerprint,
            'metadata' => [self::RESOURCE_CLASS => null],
        ]));
    }
}
