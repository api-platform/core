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

use ApiPlatform\Laravel\Eloquent\Metadata\MetadataDumpFingerprint;
use PHPUnit\Framework\TestCase;

class MetadataDumpFingerprintTest extends TestCase
{
    private string $migrationsPath;

    protected function setUp(): void
    {
        $this->migrationsPath = sys_get_temp_dir().'/apip_fingerprint_'.getmypid();
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0o755, true);
        }

        foreach (glob($this->migrationsPath.'/*.php') ?: [] as $file) {
            unlink($file);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->migrationsPath.'/*.php') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->migrationsPath)) {
            rmdir($this->migrationsPath);
        }
    }

    public function testItIsStableForUnchangedMigrations(): void
    {
        $this->writeMigration('2024_01_01_000000_create_books_table.php', 1_700_000_000);

        $first = MetadataDumpFingerprint::fromMigrations($this->migrationsPath);
        $second = MetadataDumpFingerprint::fromMigrations($this->migrationsPath);

        $this->assertSame($first, $second);
    }

    public function testItChangesWhenAMigrationIsAdded(): void
    {
        $this->writeMigration('2024_01_01_000000_create_books_table.php', 1_700_000_000);
        $before = MetadataDumpFingerprint::fromMigrations($this->migrationsPath);

        $this->writeMigration('2024_02_02_000000_create_authors_table.php', 1_700_000_100);

        $this->assertNotSame($before, MetadataDumpFingerprint::fromMigrations($this->migrationsPath));
    }

    public function testItChangesWhenAMigrationIsTouched(): void
    {
        $file = $this->writeMigration('2024_01_01_000000_create_books_table.php', 1_700_000_000);
        $before = MetadataDumpFingerprint::fromMigrations($this->migrationsPath);

        touch($file, 1_700_999_999);
        // The first fingerprint call already stat()ed the file, so its mtime is cached for this
        // process; clear it so the second call reads the value set by touch().
        clearstatcache(true, $file);

        $this->assertNotSame($before, MetadataDumpFingerprint::fromMigrations($this->migrationsPath));
    }

    public function testItIsEmptyHashForNoMigrations(): void
    {
        $this->assertNotSame('', MetadataDumpFingerprint::fromMigrations($this->migrationsPath));
        $this->assertSame(
            MetadataDumpFingerprint::fromMigrations($this->migrationsPath),
            MetadataDumpFingerprint::fromMigrations($this->migrationsPath.'/missing'),
        );
    }

    private function writeMigration(string $name, int $mtime): string
    {
        $path = $this->migrationsPath.'/'.$name;
        file_put_contents($path, '<?php // '.$name);
        touch($path, $mtime);

        return $path;
    }
}
