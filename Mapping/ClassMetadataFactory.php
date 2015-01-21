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
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface as ValidatorMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory as SerializerClassMetadataFactory;

/**
 * ClassMetadataFactory.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataFactory
{
    /**
     * @var \Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface
     */
    private $validatorMetadataFactory;
    /**
     * @var \Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory
     */
    private $serializerClassMetadataFactory;
    /**
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private $cache;
    /**
     * @var array
     */
    private $loadedClasses = [];

    public function __construct(ValidatorMetadataFactoryInterface $validatorMetadataFactory, SerializerClassMetadataFactory $serializerClassMetadataFactory, Cache $cache = null)
    {
        $this->validatorMetadataFactory = $validatorMetadataFactory;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
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
     * @param  string|object             $value
     * @return ClassMetadata
     * @throws \InvalidArgumentException
     */
    public function getMetadataFor($value)
    {
        $class = $this->getClass($value);
        if (!$class) {
            throw new \InvalidArgumentException(sprintf('Cannot create metadata for non-objects. Got: %s', gettype($value)));
        }

        if (isset($this->loadedClasses[$class])) {
            return $this->loadedClasses[$class];
        }

        if ($this->cache && ($this->loadedClasses[$class] = $this->cache->fetch($class))) {
            return $this->loadedClasses[$class];
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new \InvalidArgumentException(sprintf('The class or interface "%s" does not exist.', $class));
        }

        $serializerMetadata = $this->serializerClassMetadataFactory ? $this->serializerClassMetadataFactory->getMetadataFor($class) : null;
        $validatorMetadata = $this->validatorMetadataFactory ? $this->validatorMetadataFactory->getMetadataFor($class) : null;

        $metadata = new ClassMetadata($class, $serializerMetadata, $validatorMetadata);

        $reflClass = $metadata->getReflectionClass();

        // Include constraints from the parent class
        if ($parent = $reflClass->getParentClass()) {
            //$metadata->mergeAttributesGroups($this->getMetadataFor($parent->name));
        }

        // Include constraints from all implemented interfaces
        foreach ($reflClass->getInterfaces() as $interface) {
            //$metadata->mergeAttributesGroups($this->getMetadataFor($interface->name));
        }

        if ($this->loader) {
            $this->loader->loadClassMetadata($metadata);
        }

        if ($this->cache) {
            $this->cache->save($class, $metadata);
        }

        return $this->loadedClasses[$class] = $metadata;
    }

    /**
     * Checks if class has metadata.
     *
     * @param  mixed $value
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
     * @param $value
     * @return string|bool
     */
    private function getClass($value)
    {
        if (!is_object($value) && !is_string($value)) {
            return false;
        }

        return ltrim(is_object($value) ? get_class($value) : $value, '\\');
    }
}
