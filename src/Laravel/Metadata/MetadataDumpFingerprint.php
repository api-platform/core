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

namespace ApiPlatform\Laravel\Metadata;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use Illuminate\Database\Eloquent\Model;

/**
 * Computes freshness fingerprints for the metadata dump written by api-platform:metadata:dump.
 *
 * Two independent axes, because the dump exists to let the app boot without a database:
 *  - resources(): hashes the ApiResource source files, so it can run at boot with no DB;
 *  - schema(): hashes the live Eloquent schema, so it can only run when a DB is reachable.
 */
final class MetadataDumpFingerprint
{
    public const VERSION = 1;

    /**
     * Content hash of every PHP file under the given resource paths.
     *
     * Content-based (not filemtime) on purpose: a committed or image-baked dump must stay valid
     * across `git clone` and `docker build`, neither of which preserves modification times.
     *
     * @param list<string> $paths
     */
    public static function resources(array $paths): string
    {
        $hashes = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $hashes[$path] = sha1_file($path) ?: '';
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                    continue;
                }

                $pathname = $file->getPathname();
                $hashes[$pathname] = sha1_file($pathname) ?: '';
            }
        }

        ksort($hashes);

        return hash('xxh128', serialize($hashes));
    }

    /**
     * Hash of the live Eloquent schema (columns + relations) for every model-backed resource.
     * Requires a database connection.
     *
     * @param iterable<class-string> $resourceClasses
     */
    public static function schema(iterable $resourceClasses, ModelMetadata $modelMetadata): string
    {
        $signature = [];
        foreach ($resourceClasses as $resourceClass) {
            try {
                $model = (new \ReflectionClass($resourceClass))->newInstanceWithoutConstructor();
            } catch (\ReflectionException) {
                continue;
            }

            if (!$model instanceof Model) {
                continue;
            }

            $signature[$resourceClass] = [
                'attributes' => $modelMetadata->getAttributes($model),
                'relations' => $modelMetadata->getRelations($model),
            ];
        }

        ksort($signature);

        return hash('xxh128', serialize($signature));
    }
}
