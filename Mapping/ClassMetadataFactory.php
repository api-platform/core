<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Mapping;

use Doctrine\Common\Cache\Cache;
use Dunglas\JsonLdApiBundle\Mapping\Loader\LoaderInterface;

/**
 * Class metadata factory for the JSON-LD normalizer.
 *
 * Reuse data available through Serializer, Validator and ORM mappings when possible.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataFactory
{
    /**
     * @var LoaderInterface
     */
    private $loader;
    /**
     * @var Cache|null
     */
    private $cache;
    /**
     * @var array
     */
    private $loadedClasses = [];

    public function __construct(LoaderInterface $loader, Cache $cache = null)
    {
        $this->loader = $loader;
        $this->cache = $cache;
    }

    /**
     * If the method was called with the same class name (or an object of that
     * class) before, the same metadata instance is returned.
     *
     * If the factory was configured with a cache, this method will first look
     * for an existing metadata instance in the cache. If an existing instance
     * is found, it will be returned without further ado.
     *
     * Otherwise, a new metadata instance is created. If the factory was
     * configured with a loader, the metadata is passed to the
     * {@link LoaderInterface::loadClassMetadata()} method for further
     * configuration. At last, the new object is returned.
     *
     * @param string|object $value
     *
     * @return ClassMetadata
     *
     * @throws \InvalidArgumentException
     */
    public function getMetadataFor(
        $value,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $class = $this->getClass($value);
        if (!$class) {
            throw new \InvalidArgumentException(sprintf('Cannot create metadata for non-objects. Got: %s', gettype($value)));
        }

        $classKey = serialize([$class, $normalizationGroups, $denormalizationGroups, $validationGroups]);

        if (isset($this->loadedClasses[$classKey])) {
            return $this->loadedClasses[$classKey];
        }

        if ($this->cache && ($this->loadedClasses[$classKey] = $this->cache->fetch($classKey))) {
            return $this->loadedClasses[$classKey];
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new \InvalidArgumentException(sprintf('The class or interface "%s" does not exist.', $class));
        }

        $classMetadata = new ClassMetadata($class);
        $this->loader->loadClassMetadata(
            $classMetadata,
            $normalizationGroups,
            $denormalizationGroups,
            $denormalizationGroups
        );

        if ($this->cache) {
            $this->cache->save($classKey, $classMetadata);
        }

        return $this->loadedClasses[$classKey] = $classMetadata;
    }

    /**
     * Checks if class has metadata.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function hasMetadataFor($value)
    {
        $class = $this->getClass($value);

        return class_exists($class) || interface_exists($class);
    }

    /**
     * Gets a class name for a given class or instance.
     *
     * @param mixed $value
     *
     * @return string|false
     */
    private function getClass($value)
    {
        if (!is_object($value) && !is_string($value)) {
            return false;
        }

        return ltrim(is_object($value) ? get_class($value) : $value, '\\');
    }
}
