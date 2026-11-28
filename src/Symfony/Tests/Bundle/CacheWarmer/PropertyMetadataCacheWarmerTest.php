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

namespace ApiPlatform\Symfony\Tests\Bundle\CacheWarmer;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\CachedPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Symfony\Bundle\CacheWarmer\PropertyMetadataCacheWarmer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;

final class PropertyMetadataCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/api_platform_property_metadata_warmer_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->cacheDir);
    }

    public function testWarmsPropertyMetadataAndRemovesFallbackCopies(): void
    {
        $class = PropertyMetadataWarmupSubject::class;
        $nameKey = 'property_name_collection_'.hash('xxh3', serialize([$class, []]));
        $metadataKey = 'property_metadata_'.hash('xxh3', serialize([$class, 'name', []]));
        $fallback = new ArrayAdapter();
        $fallback->save($fallback->getItem($nameKey)->set(new PropertyNameCollection(['name'])));
        $fallback->save($fallback->getItem($metadataKey)->set(new ApiProperty(description: 'warmed')));
        $fallback->save($fallback->getItem('other')->set('keep'));

        $resources = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resources->expects($this->once())->method('create')->willReturn(new ResourceNameCollection([$class]));
        $names = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $names->expects($this->once())->method('create')->with($class, [])->willReturn(new PropertyNameCollection(['name']));
        $metadata = $this->createMock(PropertyMetadataFactoryInterface::class);
        $metadata->expects($this->once())->method('create')->with($class, 'name', [])->willReturn(new ApiProperty(description: 'warmed'));
        $file = $this->cacheDir.'/build/property_metadata.php';

        $warmer = new PropertyMetadataCacheWarmer($resources, $names, $metadata, $fallback, $file);
        $warmer->warmUp($this->cacheDir, $this->cacheDir.'/build');

        $cached = PhpArrayAdapter::create($file, new ArrayAdapter());
        $this->assertFileExists($file);
        $this->assertSame(['name'], iterator_to_array($cached->getItem($nameKey)->get()));
        $this->assertSame('warmed', $cached->getItem($metadataKey)->get()->getDescription());
        $this->assertSame(['name'], iterator_to_array((new CachedPropertyNameCollectionFactory($cached, $names))->create($class)));
        $this->assertSame('warmed', (new CachedPropertyMetadataFactory($cached, $metadata))->create($class, 'name')->getDescription());
        $this->assertFalse($fallback->hasItem($nameKey));
        $this->assertFalse($fallback->hasItem($metadataKey));
        $this->assertSame('keep', $fallback->getItem('other')->get());
    }

    public function testSkipsWarmupWithoutBuildDirectory(): void
    {
        $resources = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resources->expects($this->never())->method('create');
        $names = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $names->expects($this->never())->method('create');
        $metadata = $this->createMock(PropertyMetadataFactoryInterface::class);
        $metadata->expects($this->never())->method('create');
        $file = $this->cacheDir.'/build/property_metadata.php';
        $warmer = new PropertyMetadataCacheWarmer(
            $resources,
            $names,
            $metadata,
            new ArrayAdapter(),
            $file,
        );

        $this->assertTrue($warmer->isOptional());
        $this->assertSame([], $warmer->warmUp($this->cacheDir));
        $this->assertFileDoesNotExist($file);
    }
}

final class PropertyMetadataWarmupSubject
{
    public string $name;
}
