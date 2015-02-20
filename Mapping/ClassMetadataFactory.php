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
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory as DoctrineClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface as ValidatorMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory as SerializerClassMetadataFactory;

/**
 * ClassMetadata Factory for the JSON-LD normalizer.
 *
 * Reuse data available through Serializer, Validator and ORM mappings when possible.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataFactory
{
    /**
     * @var ValidatorMetadataFactory|null
     */
    private $validatorMetadataFactory;
    /**
     * @var SerializerClassMetadataFactory|null
     */
    private $serializerClassMetadataFactory;
    /**
     * @var DoctrineClassMetadataFactory|null
     */
    private $doctrineClassMetadataFactory;
    /**
     * @var Cache|null
     */
    private $cache;
    /**
     * @var array
     */
    private $loadedClasses = [];

    public function __construct(
        ValidatorMetadataFactory $validatorMetadataFactory = null,
        SerializerClassMetadataFactory $serializerClassMetadataFactory = null,
        DoctrineClassMetadataFactory $doctrineClassMetadataFactory = null,
        Cache $cache = null
    ) {
        $this->validatorMetadataFactory = $validatorMetadataFactory;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
        $this->doctrineClassMetadataFactory = $doctrineClassMetadataFactory;
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
        $doctrineMetadata = $this->doctrineClassMetadataFactory ? $this->doctrineClassMetadataFactory->getMetadataFor($class) : null;

        $metadata = new ClassMetadata($class, $serializerMetadata, $validatorMetadata, $doctrineMetadata);

        if ($this->cache) {
            $this->cache->save($class, $metadata);
        }

        return $this->loadedClasses[$class] = $metadata;
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
     * Gets Serializer's ClassMetadataFactory.
     *
     * @return SerializerClassMetadataFactory|null
     */
    public function getSerializerClassMetadataFactory()
    {
        return $this->serializerClassMetadataFactory;
    }

    /**
     * Gets a class name for a given class or instance.
     *
     * @param $value
     *
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
