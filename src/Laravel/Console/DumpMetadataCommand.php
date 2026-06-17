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

namespace ApiPlatform\Laravel\Console;

use ApiPlatform\Laravel\Eloquent\Metadata\MetadataDumpFingerprint;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'api-platform:metadata:dump')]
final class DumpMetadataCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'api-platform:metadata:dump {--path= : Where to write the dumped metadata file (defaults to the api-platform.metadata_dump config value)}';

    /**
     * @var string
     */
    protected $description = 'Dump the Eloquent model metadata to a file so the app can boot without hitting the database';

    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ModelMetadata $modelMetadata,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->option('path');
        if (null === $path || '' === $path) {
            $path = config('api-platform.metadata_dump');
        }

        if (!\is_string($path) || '' === $path) {
            $this->error('No dump path configured. Pass --path or set the "api-platform.metadata_dump" config value.');

            return self::FAILURE;
        }

        // This command is bound to a live ModelMetadata (contextual binding) so introspection reads
        // the database rather than a previously dumped, possibly stale, cache.
        $attributes = [];
        $relations = [];
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            try {
                $model = (new \ReflectionClass($resourceClass))->newInstanceWithoutConstructor();
            } catch (\ReflectionException) {
                continue;
            }

            if (!$model instanceof Model) {
                continue;
            }

            $attributes[$resourceClass] = $this->modelMetadata->getAttributes($model);
            $relations[$resourceClass] = $this->modelMetadata->getRelations($model);
        }

        $directory = \dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            $this->error(\sprintf('Unable to create directory "%s".', $directory));

            return self::FAILURE;
        }

        // Write to a temporary file then rename so a concurrent boot never reads a half-written dump.
        $payload = serialize([
            'fingerprint' => MetadataDumpFingerprint::fromMigrations($this->laravel->databasePath('migrations')),
            'attributes' => $attributes,
            'relations' => $relations,
        ]);
        $temporaryPath = $path.'.'.getmypid().'.tmp';
        if (false === file_put_contents($temporaryPath, $payload) || !rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            $this->error(\sprintf('Unable to write the metadata dump to "%s".', $path));

            return self::FAILURE;
        }

        $this->info(\sprintf('Dumped metadata for %d model(s) to "%s".', \count($attributes), $path));

        return self::SUCCESS;
    }
}
