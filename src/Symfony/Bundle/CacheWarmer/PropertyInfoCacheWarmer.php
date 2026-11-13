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

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

final class PropertyInfoCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly PropertyInfoExtractorInterface $extractor,
        private readonly ResourceNameCollectionFactoryInterface $resourceNames,
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
        $extractor = new PropertyInfoCacheExtractor($this->extractor, $pool);

        spl_autoload_register([ClassExistenceResource::class, 'throwOnRequiredClass']);
        try {
            foreach ($this->resourceNames->create() as $class) {
                try {
                    $properties = array_unique(array_merge(
                        $extractor->getProperties($class) ?? [],
                        $extractor->getProperties($class, ['serializer_groups' => null]) ?? [],
                    ));

                    foreach ($properties as $property) {
                        $extractor->getType($class, $property);
                        $extractor->getShortDescription($class, $property);
                        $extractor->isReadable($class, $property);
                        $extractor->isWritable($class, $property);
                        $extractor->isInitializable($class, $property);
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

        return (new PhpArrayAdapter($this->file, new NullAdapter()))->warmUp($pool->getValues());
    }
}
