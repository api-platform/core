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

use ApiPlatform\Laravel\Eloquent\Metadata\MetadataDumpFingerprint;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Book;

class DumpedMetadataBootTest extends TestCase
{
    use WithWorkbench;

    private const CANNED_ATTRIBUTES = [
        'name' => ['name' => 'name', 'type' => 'string', 'nullable' => false],
    ];

    private const CANNED_RELATIONS = [
        'author' => ['name' => 'author', 'method_name' => 'author', 'related' => 'Workbench\\App\\Models\\Author'],
    ];

    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'apip_boot_dump_').'.meta';

        file_put_contents($this->dumpPath, serialize([
            'attributes' => [Book::class => self::CANNED_ATTRIBUTES],
            'relations' => [Book::class => self::CANNED_RELATIONS],
        ]));

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

    public function testItServesModelMetadataFromTheDumpWithoutHittingTheDatabase(): void
    {
        $modelMetadata = $this->app->make(ModelMetadata::class);

        $book = (new \ReflectionClass(Book::class))->newInstanceWithoutConstructor();

        $this->assertSame(self::CANNED_ATTRIBUTES, $modelMetadata->getAttributes($book));
        $this->assertSame(self::CANNED_RELATIONS, $modelMetadata->getRelations($book));
    }

    public function testItWarnsWhenTheDumpFingerprintIsStale(): void
    {
        // The canned dump written in setUp() carries no fingerprint, so it never matches the
        // current migrations and must be reported as stale.
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(static fn (string $message): bool => str_contains($message, 'stale'));
        $this->app->forgetInstance(ModelMetadata::class);

        $this->app->make(ModelMetadata::class);
    }

    public function testItDoesNotWarnWhenTheFingerprintMatches(): void
    {
        file_put_contents($this->dumpPath, serialize([
            'fingerprint' => MetadataDumpFingerprint::fromMigrations($this->app->databasePath('migrations')),
            'attributes' => [Book::class => self::CANNED_ATTRIBUTES],
            'relations' => [Book::class => self::CANNED_RELATIONS],
        ]));

        Log::shouldReceive('warning')->never();
        $this->app->forgetInstance(ModelMetadata::class);

        $this->app->make(ModelMetadata::class);
    }

    public function testItIsNotSeededWhenDebugIsEnabled(): void
    {
        $this->app['config']->set('app.debug', true);
        $this->app->forgetInstance(ModelMetadata::class);

        $modelMetadata = $this->app->make(ModelMetadata::class);

        $reflection = new \ReflectionProperty(ModelMetadata::class, 'attributes');

        $this->assertSame([], $reflection->getValue($modelMetadata), 'A debug boot must return an unseeded ModelMetadata that introspects the database.');
    }
}
