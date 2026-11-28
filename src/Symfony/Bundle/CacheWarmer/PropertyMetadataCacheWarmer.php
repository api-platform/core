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

namespace ApiPlatform\Symfony\Bundle\CacheWarmer;

use ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\CachedPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class PropertyMetadataCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resources,
        private readonly PropertyNameCollectionFactoryInterface $names,
        private readonly PropertyMetadataFactoryInterface $metadata,
        private readonly CacheItemPoolInterface $fallbackPool,
        private readonly string $file,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if (null === $buildDir) {
            return [];
        }

        $pool = new ArrayAdapter(0, false);
        $names = new CachedPropertyNameCollectionFactory($pool, $this->names);
        $metadata = new CachedPropertyMetadataFactory($pool, $this->metadata);

        spl_autoload_register([ClassExistenceResource::class, 'throwOnRequiredClass']);
        try {
            foreach ($this->resources->create() as $class) {
                try {
                    foreach ($names->create($class) as $property) {
                        $metadata->create($class, $property);
                    }
                } catch (\Exception $e) {
                    try {
                        ClassExistenceResource::throwOnRequiredClass($class, $e);
                    } catch (\ReflectionException) {
                    }
                }
            }
        } finally {
            spl_autoload_unregister([ClassExistenceResource::class, 'throwOnRequiredClass']);
        }

        $values = $pool->getValues();
        $preload = (new PhpArrayAdapter($this->file, new NullAdapter()))->warmUp($values);
        $this->fallbackPool->deleteItems(array_keys($values));

        return $preload;
    }
}
