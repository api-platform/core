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

namespace ApiPlatform\Laravel\Tests\Unit\Metadata;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Metadata\MetadataDumpFingerprint;
use PHPUnit\Framework\TestCase;

class MetadataDumpFingerprintTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/apip_fp_'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/nested', 0o755, true);
        file_put_contents($this->dir.'/A.php', '<?php class A {}');
        file_put_contents($this->dir.'/nested/B.php', '<?php class B {}');
    }

    protected function tearDown(): void
    {
        foreach ([$this->dir.'/nested/B.php', $this->dir.'/A.php', $this->dir.'/nested', $this->dir] as $path) {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function testResourcesHashIsStableAcrossCalls(): void
    {
        $this->assertSame(
            MetadataDumpFingerprint::resources([$this->dir]),
            MetadataDumpFingerprint::resources([$this->dir]),
        );
    }

    public function testResourcesHashChangesWhenAFileContentChanges(): void
    {
        $before = MetadataDumpFingerprint::resources([$this->dir]);

        file_put_contents($this->dir.'/A.php', '<?php class A { public int $added = 1; }');

        $this->assertNotSame($before, MetadataDumpFingerprint::resources([$this->dir]));
    }

    public function testResourcesHashIgnoresNonPhpFiles(): void
    {
        $before = MetadataDumpFingerprint::resources([$this->dir]);

        file_put_contents($this->dir.'/notes.txt', 'not a resource');
        $after = MetadataDumpFingerprint::resources([$this->dir]);
        unlink($this->dir.'/notes.txt');

        $this->assertSame($before, $after);
    }

    public function testResourcesHashSkipsMissingPaths(): void
    {
        $this->assertSame(
            MetadataDumpFingerprint::resources([$this->dir]),
            MetadataDumpFingerprint::resources([$this->dir, $this->dir.'/does-not-exist']),
        );
    }

    public function testSchemaSkipsClassesThatAreNotEloquentModels(): void
    {
        // Non-model and non-existent classes must never reach the database introspection,
        // so the signature stays empty-equivalent regardless of how many are passed.
        $modelMetadata = new ModelMetadata();

        $this->assertSame(
            MetadataDumpFingerprint::schema([], $modelMetadata),
            MetadataDumpFingerprint::schema([self::class, 'App\\Does\\Not\\Exist'], $modelMetadata),
        );
    }
}
