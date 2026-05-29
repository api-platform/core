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

namespace ApiPlatform\Tests;

use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

trait WithResourcesTrait
{
    /**
     * @param class-string[] $resources
     */
    protected static function writeResources(array $resources): void
    {
        file_put_contents(__DIR__.'/Fixtures/app/var/resources.php', \sprintf('<?php return [%s];', implode(',', array_map(static fn ($v) => $v.'::class', $resources))));
        self::invalidateMetadataPools();
    }

    protected static function removeResources(): void
    {
        file_put_contents(__DIR__.'/Fixtures/app/var/resources.php', '<?php return [];');
        self::invalidateMetadataPools();
    }

    /**
     * Per-test-class resource subsetting changes which classes pass `isResourceClass()`,
     * which in turn changes the entries computed by `LinkResourceMetadataCollectionFactory`
     * and stored by `CachedResourceMetadataCollectionFactory`. That cache is keyed by class
     * name only, so entries computed under a smaller resource set leak into later classes
     * that register a superset. We clear both the on-disk pool directories and the
     * process-lifetime `PhpFilesAdapter::$valuesCache` (Symfony's "append-only" pools
     * memoise file contents statically once read, which would otherwise keep serving the
     * stale entry even after the file is gone).
     */
    private static function invalidateMetadataPools(): void
    {
        foreach (glob(__DIR__.'/Fixtures/app/var/cache/*/pools') ?: [] as $poolsDir) {
            self::removeDirectory($poolsDir);
        }

        $reflection = new \ReflectionClass(PhpFilesAdapter::class);
        if ($reflection->hasProperty('valuesCache')) {
            $property = $reflection->getProperty('valuesCache');
            $property->setValue(null, []);
        }
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($dir);
    }
}
