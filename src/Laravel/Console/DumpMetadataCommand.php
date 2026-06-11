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

use ApiPlatform\Laravel\Metadata\DumpedResourceCollectionMetadataFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Illuminate\Console\Command;
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
    protected $description = 'Dump the resource metadata to a file so the app can boot without hitting the database';

    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->option('path') ?: config('api-platform.metadata_dump');

        if (!\is_string($path) || '' === $path) {
            $this->error('No dump path configured. Pass --path or set the "api-platform.metadata_dump" config value.');

            return self::FAILURE;
        }

        // Always rebuild from the live source, never from a previously dumped (possibly stale) file.
        $factory = $this->resourceMetadataCollectionFactory;
        while ($factory instanceof DumpedResourceCollectionMetadataFactory) {
            $factory = $factory->getDecorated();
        }

        $metadata = [];
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $metadata[$resourceClass] = $factory->create($resourceClass);
        }

        $directory = \dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            $this->error(\sprintf('Unable to create directory "%s".', $directory));

            return self::FAILURE;
        }

        if (false === file_put_contents($path, serialize($metadata))) {
            $this->error(\sprintf('Unable to write the metadata dump to "%s".', $path));

            return self::FAILURE;
        }

        $this->info(\sprintf('Dumped metadata for %d resource(s) to "%s".', \count($metadata), $path));

        return self::SUCCESS;
    }
}
