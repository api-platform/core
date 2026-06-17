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

namespace ApiPlatform\Laravel\Eloquent\Metadata;

/**
 * Fingerprints the migration files so a stale metadata dump (migrations changed since it was
 * generated) can be detected at boot without reading the database, which would defeat the dump.
 *
 * It hashes file names, modification times and sizes only: a manual schema change made outside the
 * migration files is not detected.
 *
 * @internal
 */
final class MetadataDumpFingerprint
{
    public static function fromMigrations(string $migrationsPath): string
    {
        $files = glob($migrationsPath.'/*.php') ?: [];
        sort($files);

        $hash = hash_init('xxh128');
        foreach ($files as $file) {
            hash_update($hash, $file.'|'.filemtime($file).'|'.filesize($file));
        }

        return hash_final($hash);
    }
}
