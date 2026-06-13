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
use Psr\Log\LoggerInterface;

/**
 * Serves the resource metadata from a file dumped by api-platform:metadata:dump, bypassing the
 * database introspection that happens while building the collection. Delegates to the decorated
 * factory for any resource missing from the dump (or when no dump file exists).
 *
 * When the dump carries a resources fingerprint, it is checked against the current source files
 * once on load: a mismatch logs a warning (the dump is still served, so the no-database boot keeps
 * working) telling the operator to re-run api-platform:metadata:dump. Database schema drift cannot
 * be detected here without a connection; it is reported by the migrate listener instead.
 */
final class DumpedResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @var array<class-string, ResourceMetadataCollection>|null
     */
    private ?array $dumped = null;

    /**
     * @param list<string> $resourcePaths
     */
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ?string $dumpPath,
        private readonly ?LoggerInterface $logger = null,
        private readonly array $resourcePaths = [],
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
        if (!\is_array($data)) {
            return $this->dumped = [];
        }

        // An envelope (version >= 1) carries fingerprints; a bare map is an older dump with no
        // freshness information — serve it as-is without a staleness check.
        if (!isset($data['version'], $data['metadata']) || !\is_array($data['metadata'])) {
            return $this->dumped = $data;
        }

        $this->warnIfResourcesChanged(\is_string($data['resources_fingerprint'] ?? null) ? $data['resources_fingerprint'] : null);

        return $this->dumped = $data['metadata'];
    }

    private function warnIfResourcesChanged(?string $dumpedFingerprint): void
    {
        if (null === $dumpedFingerprint || null === $this->logger || [] === $this->resourcePaths) {
            return;
        }

        if (MetadataDumpFingerprint::resources($this->resourcePaths) === $dumpedFingerprint) {
            return;
        }

        $this->logger->warning('The API Platform metadata dump at "{path}" is stale: resource files changed since it was generated. Run "php artisan api-platform:metadata:dump" to refresh it.', ['path' => $this->dumpPath]);
    }
}
