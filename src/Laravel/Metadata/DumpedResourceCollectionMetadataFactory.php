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

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Serves the resource metadata from a file dumped by api-platform:metadata:dump, bypassing the
 * database introspection that happens while building the collection. Delegates to the decorated
 * factory for any resource missing from the dump (or when no dump file exists).
 */
final class DumpedResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @var array<class-string, ResourceMetadataCollection>|null
     */
    private ?array $dumped = null;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ?string $dumpPath,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $dumped = $this->load();

        return $dumped[$resourceClass] ?? $this->decorated->create($resourceClass);
    }

    /**
     * Exposes the decorated factory so the dump command can rebuild metadata from the live source
     * instead of reading back a previously dumped (possibly stale) file.
     */
    public function getDecorated(): ResourceMetadataCollectionFactoryInterface
    {
        return $this->decorated;
    }

    /**
     * @return array<class-string, ResourceMetadataCollection>
     */
    private function load(): array
    {
        if (null !== $this->dumped) {
            return $this->dumped;
        }

        if (null === $this->dumpPath || !is_file($this->dumpPath)) {
            return $this->dumped = [];
        }

        $contents = file_get_contents($this->dumpPath);
        if (false === $contents) {
            return $this->dumped = [];
        }

        $data = unserialize($contents, ['allowed_classes' => true]);

        return $this->dumped = \is_array($data) ? $data : [];
    }
}
