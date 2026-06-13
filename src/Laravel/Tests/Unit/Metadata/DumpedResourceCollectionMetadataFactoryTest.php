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

use ApiPlatform\Laravel\Metadata\DumpedResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\MetadataDumpFingerprint;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Workbench\App\Models\Book;

class DumpedResourceCollectionMetadataFactoryTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'apip_dump_').'.meta';
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }
    }

    public function testItReturnsTheDumpedCollectionWithoutCallingTheDecoratedFactory(): void
    {
        $dumped = new ResourceMetadataCollection(Book::class, [new ApiResource(shortName: 'Book')]);
        file_put_contents($this->dumpPath, serialize([Book::class => $dumped]));

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->never())->method('create');

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, $this->dumpPath);

        $result = $factory->create(Book::class);

        $this->assertEquals($dumped, $result);
    }

    public function testItDelegatesForAClassMissingFromTheDump(): void
    {
        file_put_contents($this->dumpPath, serialize([Book::class => new ResourceMetadataCollection(Book::class, [])]));

        $expected = new ResourceMetadataCollection('Unknown', [new ApiResource(shortName: 'Unknown')]);
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with('Unknown')->willReturn($expected);

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, $this->dumpPath);

        $this->assertSame($expected, $factory->create('Unknown'));
    }

    public function testItDelegatesWhenNoDumpPathConfigured(): void
    {
        $expected = new ResourceMetadataCollection(Book::class, [new ApiResource(shortName: 'Book')]);
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(Book::class)->willReturn($expected);

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, null);

        $this->assertSame($expected, $factory->create(Book::class));
    }

    public function testItDelegatesWhenDumpFileIsAbsent(): void
    {
        $expected = new ResourceMetadataCollection(Book::class, [new ApiResource(shortName: 'Book')]);
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(Book::class)->willReturn($expected);

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, '/nonexistent/path/api_platform_metadata.meta');

        $this->assertSame($expected, $factory->create(Book::class));
    }

    public function testItServesABareMapDumpWithoutAnyStalenessWarning(): void
    {
        // Old (pre-fingerprint) dump format: a plain map with no envelope.
        $dumped = new ResourceMetadataCollection(Book::class, [new ApiResource(shortName: 'Book')]);
        file_put_contents($this->dumpPath, serialize([Book::class => $dumped]));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->never())->method('create');

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, $this->dumpPath, $logger, [__DIR__]);

        $this->assertEquals($dumped, $factory->create(Book::class));
    }

    public function testItWarnsButStillServesTheDumpWhenResourceFilesChanged(): void
    {
        $dumped = new ResourceMetadataCollection(Book::class, [new ApiResource(shortName: 'Book')]);
        file_put_contents($this->dumpPath, serialize($this->envelope([Book::class => $dumped], 'a-stale-fingerprint')));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->never())->method('create');

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, $this->dumpPath, $logger, [__DIR__]);

        // Dump is still served despite the staleness warning (no-DB boot must keep working).
        $this->assertEquals($dumped, $factory->create(Book::class));
    }

    public function testItDoesNotWarnWhenTheResourceFingerprintMatches(): void
    {
        $dumped = new ResourceMetadataCollection(Book::class, [new ApiResource(shortName: 'Book')]);
        $fingerprint = MetadataDumpFingerprint::resources([__DIR__]);
        file_put_contents($this->dumpPath, serialize($this->envelope([Book::class => $dumped], $fingerprint)));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);

        $factory = new DumpedResourceCollectionMetadataFactory($decorated, $this->dumpPath, $logger, [__DIR__]);

        $this->assertEquals($dumped, $factory->create(Book::class));
    }

    /**
     * @param array<class-string, ResourceMetadataCollection> $metadata
     *
     * @return array{version: int, resources_fingerprint: string, schema_fingerprint: string, metadata: array<class-string, ResourceMetadataCollection>}
     */
    private function envelope(array $metadata, string $resourcesFingerprint): array
    {
        return [
            'version' => MetadataDumpFingerprint::VERSION,
            'resources_fingerprint' => $resourcesFingerprint,
            'schema_fingerprint' => '',
            'metadata' => $metadata,
        ];
    }
}
