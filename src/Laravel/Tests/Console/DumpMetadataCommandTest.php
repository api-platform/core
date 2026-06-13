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

namespace ApiPlatform\Laravel\Tests\Console;

use ApiPlatform\Laravel\Metadata\DumpedResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\MetadataDumpFingerprint;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Illuminate\Console\Command;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DumpMetadataCommandTest extends TestCase
{
    use WithWorkbench;

    private string $dumpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dumpPath = tempnam(sys_get_temp_dir(), 'apip_dump_cmd_').'.meta';
        @unlink($this->dumpPath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }

        parent::tearDown();
    }

    public function testItDumpsTheResourceMetadataCollectionMapToTheGivenFile(): void
    {
        $classOne = 'App\\Resource\\One';
        $classTwo = 'App\\Resource\\Two';

        $collectionOne = new ResourceMetadataCollection($classOne, [new ApiResource(shortName: 'One')]);
        $collectionTwo = new ResourceMetadataCollection($classTwo, [new ApiResource(shortName: 'Two')]);

        $nameFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $nameFactory->method('create')->willReturn(new ResourceNameCollection([$classOne, $classTwo]));

        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory->method('create')->willReturnCallback(static fn (string $class): ResourceMetadataCollection => match ($class) {
            $classOne => $collectionOne,
            $classTwo => $collectionTwo,
            default => throw new \LogicException(\sprintf('Unexpected class "%s".', $class)),
        });

        $this->app->instance(ResourceNameCollectionFactoryInterface::class, $nameFactory);
        $this->app->instance(ResourceMetadataCollectionFactoryInterface::class, $metadataFactory);

        $this->runDump()
            ->expectsOutputToContain('Dumped metadata for')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($this->dumpPath);

        $dumped = $this->readDump();

        $this->assertSame(MetadataDumpFingerprint::VERSION, $dumped['version']);
        $this->assertIsString($dumped['resources_fingerprint']);
        $this->assertIsString($dumped['schema_fingerprint']);

        $metadata = $dumped['metadata'];
        $this->assertArrayHasKey($classOne, $metadata);
        $this->assertArrayHasKey($classTwo, $metadata);
        $this->assertEquals($collectionOne, $metadata[$classOne]);
        $this->assertEquals($collectionTwo, $metadata[$classTwo]);
    }

    public function testItRebuildsFromTheLiveSourceEvenWhenTheResolvedFactoryIsTheDumpedDecorator(): void
    {
        $class = 'App\\Resource\\Fresh';

        $fresh = new ResourceMetadataCollection($class, [new ApiResource(shortName: 'Fresh')]);
        $stale = new ResourceMetadataCollection($class, [new ApiResource(shortName: 'Stale')]);

        // Simulate an already-present (stale) dump on disk.
        file_put_contents($this->dumpPath, serialize([$class => $stale]));

        $nameFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $nameFactory->method('create')->willReturn(new ResourceNameCollection([$class]));

        $live = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $live->method('create')->willReturn($fresh);

        // The resolved factory is a DumpedResourceCollectionMetadataFactory pointing at the stale file.
        $dumpedFactory = new DumpedResourceCollectionMetadataFactory($live, $this->dumpPath);

        $this->app->instance(ResourceNameCollectionFactoryInterface::class, $nameFactory);
        $this->app->instance(ResourceMetadataCollectionFactoryInterface::class, $dumpedFactory);

        $this->runDump()
            ->expectsOutputToContain('Dumped metadata for')
            ->assertExitCode(Command::SUCCESS);

        $dumped = $this->readDump();

        $this->assertEquals($fresh, $dumped['metadata'][$class]);
    }

    private function runDump(): PendingCommand
    {
        $command = $this->artisan('api-platform:metadata:dump', ['--path' => $this->dumpPath]);
        if (!$command instanceof PendingCommand) {
            $this->fail('artisan() did not return a PendingCommand.');
        }

        return $command;
    }

    /**
     * @return array<string, mixed>
     */
    private function readDump(): array
    {
        $contents = file_get_contents($this->dumpPath);
        if (false === $contents) {
            $this->fail(\sprintf('Unable to read the dump file "%s".', $this->dumpPath));
        }

        $dumped = unserialize($contents, ['allowed_classes' => true]);
        if (!\is_array($dumped)) {
            $this->fail('The dump file did not contain an array.');
        }

        return $dumped;
    }
}
