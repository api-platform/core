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

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Symfony\Bundle\CacheWarmer\PropertyInfoCacheWarmer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

final class PropertyInfoCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/api_platform_property_info_warmer_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->cacheDir);
    }

    public function testWarmsResourcePropertiesAndKeepsTheFallback(): void
    {
        $reflection = new ReflectionExtractor();
        $extractor = new PropertyInfoExtractor([$reflection], [$reflection], [], [$reflection], [$reflection]);
        $resourceNames = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNames->expects($this->once())->method('create')->willReturn(new ResourceNameCollection([PropertyInfoWarmupSubject::class]));
        $file = $this->cacheDir.'/build/property_info.php';

        $warmer = new PropertyInfoCacheWarmer($extractor, $resourceNames, $file);
        $warmer->warmUp($this->cacheDir, $this->cacheDir.'/build');

        $this->assertFileExists($file);

        $fallback = new ArrayAdapter();
        $cached = new PropertyInfoCacheExtractor($extractor, PhpArrayAdapter::create($file, $fallback));

        $this->assertContains('name', $cached->getProperties(PropertyInfoWarmupSubject::class));
        $this->assertContains('name', $cached->getProperties(PropertyInfoWarmupSubject::class, ['serializer_groups' => null]));
        $this->assertEquals($extractor->getType(PropertyInfoWarmupSubject::class, 'name'), $cached->getType(PropertyInfoWarmupSubject::class, 'name'));
        $this->assertNull($cached->getShortDescription(PropertyInfoWarmupSubject::class, 'name'));
        $this->assertSame($extractor->isReadable(PropertyInfoWarmupSubject::class, 'name'), $cached->isReadable(PropertyInfoWarmupSubject::class, 'name'));
        $this->assertSame($extractor->isWritable(PropertyInfoWarmupSubject::class, 'name'), $cached->isWritable(PropertyInfoWarmupSubject::class, 'name'));
        $this->assertSame($extractor->isInitializable(PropertyInfoWarmupSubject::class, 'name'), $cached->isInitializable(PropertyInfoWarmupSubject::class, 'name'));

        foreach (['getType', 'getShortDescription', 'isReadable', 'isWritable', 'isInitializable'] as $method) {
            $key = rawurlencode($method.'.'.serialize([PropertyInfoWarmupSubject::class, 'name', []]));
            $this->assertFalse($fallback->hasItem($key), $method.' must use the warmed cache.');
        }

        $cached->getShortDescription(PropertyInfoUnknownSubject::class, 'name');
        $key = rawurlencode('getShortDescription.'.serialize([PropertyInfoUnknownSubject::class, 'name', []]));
        $this->assertTrue($fallback->hasItem($key));
    }

    public function testSkipsWarmupWithoutBuildDirectory(): void
    {
        $resourceNames = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNames->expects($this->never())->method('create');
        $file = $this->cacheDir.'/build/property_info.php';
        $warmer = new PropertyInfoCacheWarmer(new PropertyInfoExtractor(), $resourceNames, $file);

        $this->assertTrue($warmer->isOptional());
        $this->assertSame([], $warmer->warmUp($this->cacheDir));
        $this->assertFileDoesNotExist($file);
    }

    public function testMissingResourceDoesNotStopWarmup(): void
    {
        $reflection = new ReflectionExtractor();
        $extractor = new PropertyInfoExtractor([$reflection], [$reflection], [], [$reflection], [$reflection]);
        $resourceNames = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNames->expects($this->once())->method('create')->willReturn(new ResourceNameCollection([__NAMESPACE__.'\\PropertyInfoMissingSubject', PropertyInfoWarmupSubject::class]));
        $file = $this->cacheDir.'/build/property_info.php';

        (new PropertyInfoCacheWarmer($extractor, $resourceNames, $file))->warmUp($this->cacheDir, $this->cacheDir.'/build');

        $fallback = new ArrayAdapter();
        $cached = new PropertyInfoCacheExtractor($extractor, PhpArrayAdapter::create($file, $fallback));
        $this->assertContains('name', $cached->getProperties(PropertyInfoWarmupSubject::class));
        $this->assertSame([], $fallback->getValues());
    }
}

final class PropertyInfoWarmupSubject
{
    public string $name;
}

final class PropertyInfoUnknownSubject
{
    public string $name;
}
