<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Factory;

use Doctrine\Common\Cache\Cache;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Mapping\ClassMetadata;
use Dunglas\ApiBundle\Mapping\Loader\LoaderInterface;
use Dunglas\ApiBundle\Util\ClassInfoTrait;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    use ClassInfoTrait;

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
     * {@inheritdoc}
     */
    public function getMetadataFor(
        $value,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $class = $this->getClass($value);
        if (false === $class) {
            throw new InvalidArgumentException(sprintf('Cannot create metadata for non-objects. Got: %s', gettype($value)));
        }

        $classKey = serialize([$class, $normalizationGroups, $denormalizationGroups, $validationGroups]);

        if (isset($this->loadedClasses[$classKey])) {
            return $this->loadedClasses[$classKey];
        }

        if ($this->cache && ($this->loadedClasses[$classKey] = $this->cache->fetch($classKey))) {
            return $this->loadedClasses[$classKey];
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new InvalidArgumentException(sprintf('The class or interface "%s" does not exist.', $class));
        }

        $classMetadata = new ClassMetadata($class);
        $classMetadata = $this->loader->loadClassMetadata(
            $classMetadata,
            $normalizationGroups,
            $denormalizationGroups,
            $validationGroups
        );

        if ($this->cache) {
            $this->cache->save($classKey, $classMetadata);
        }

        return $this->loadedClasses[$classKey] = $classMetadata;
    }

    /**
     * {@inheritdoc}
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

        return ltrim(is_object($value) ? $this->getObjectClass($value) : $value, '\\');
    }
}
