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

use ApiPlatform\Laravel\Console\DumpMetadataCommand;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Illuminate\Console\Command;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;

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

    public function testItDumpsTheSeededModelMetadataToTheGivenFile(): void
    {
        $bookAttributes = ['name' => ['name' => 'name', 'type' => 'string']];
        $bookRelations = ['author' => ['name' => 'author', 'related' => Author::class]];
        $authorAttributes = ['id' => ['name' => 'id', 'type' => 'integer']];
        $authorRelations = [];

        $this->seedCommandModelMetadata(
            attributes: [Book::class => $bookAttributes, Author::class => $authorAttributes],
            relations: [Book::class => $bookRelations, Author::class => $authorRelations],
        );
        $this->stubResourceClasses([Book::class, Author::class]);

        $this->runDump()
            ->expectsOutputToContain('Dumped metadata for 2 model(s)')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($this->dumpPath);

        $dumped = $this->readDump();

        $this->assertSame(['fingerprint', 'attributes', 'relations'], array_keys($dumped));
        $this->assertIsString($dumped['fingerprint']);
        $this->assertSame($bookAttributes, $dumped['attributes'][Book::class]);
        $this->assertSame($authorAttributes, $dumped['attributes'][Author::class]);
        $this->assertSame($bookRelations, $dumped['relations'][Book::class]);
        $this->assertSame($authorRelations, $dumped['relations'][Author::class]);
    }

    public function testItSkipsResourceClassesThatAreNotEloquentModels(): void
    {
        $bookAttributes = ['name' => ['name' => 'name', 'type' => 'string']];

        $this->seedCommandModelMetadata(
            attributes: [Book::class => $bookAttributes],
            relations: [Book::class => []],
        );
        $this->stubResourceClasses([Book::class, 'App\\Resource\\NotAModel', \DateTimeImmutable::class]);

        $this->runDump()
            ->expectsOutputToContain('Dumped metadata for 1 model(s)')
            ->assertExitCode(Command::SUCCESS);

        $dumped = $this->readDump();

        $this->assertSame([Book::class], array_keys($dumped['attributes']));
        $this->assertSame([Book::class], array_keys($dumped['relations']));
    }

    public function testItFailsWhenNoPathIsResolvable(): void
    {
        $this->app['config']->set('api-platform.metadata_dump', null);
        $this->stubResourceClasses([]);

        $command = $this->artisan('api-platform:metadata:dump');
        if (!$command instanceof PendingCommand) {
            $this->fail('artisan() did not return a PendingCommand.');
        }

        $command
            ->expectsOutputToContain('No dump path configured')
            ->assertExitCode(Command::FAILURE);
    }

    /**
     * @param array<class-string, array<string, mixed>> $attributes
     * @param array<class-string, array<string, mixed>> $relations
     */
    private function seedCommandModelMetadata(array $attributes, array $relations): void
    {
        $this->app->when(DumpMetadataCommand::class)
            ->needs(ModelMetadata::class)
            ->give(static fn (): ModelMetadata => new ModelMetadata(attributes: $attributes, relations: $relations));
    }

    /**
     * @param list<string> $classes
     */
    private function stubResourceClasses(array $classes): void
    {
        $nameFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $nameFactory->method('create')->willReturn(new ResourceNameCollection($classes));

        $this->app->instance(ResourceNameCollectionFactoryInterface::class, $nameFactory);
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
     * @return array{fingerprint: string, attributes: array<mixed, mixed>, relations: array<mixed, mixed>}
     */
    private function readDump(): array
    {
        $contents = file_get_contents($this->dumpPath);
        if (false === $contents) {
            $this->fail(\sprintf('Unable to read the dump file "%s".', $this->dumpPath));
        }

        $dumped = unserialize($contents, ['allowed_classes' => false]);
        if (!\is_array($dumped)) {
            $this->fail('The dump file did not contain an array.');
        }

        $fingerprint = $dumped['fingerprint'] ?? null;
        $attributes = $dumped['attributes'] ?? null;
        $relations = $dumped['relations'] ?? null;
        if (!\is_string($fingerprint) || !\is_array($attributes) || !\is_array($relations)) {
            $this->fail('The dump file did not contain the expected structure.');
        }

        return ['fingerprint' => $fingerprint, 'attributes' => $attributes, 'relations' => $relations];
    }
}
