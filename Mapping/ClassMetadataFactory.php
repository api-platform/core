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
use Dunglas\JsonLdApiBundle\JsonLd\Resources;
use phpDocumentor\Reflection\FileReflector;
use PropertyInfo\PropertyInfoInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface as ValidatorMetadataFactory;

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
     * @var string[] A list of constraint classes making the entity required.
     */
    public static $requiredConstraints = [
        'Symfony\Component\Validator\Constraints\NotBlank',
        'Symfony\Component\Validator\Constraints\NotNull',
    ];
    /**
     * @var FileReflector[]
     */
    private static $fileReflectors = [];
    /**
     * @var ClassReflector[]
     */
    private static $classReflectors = [];
    /**
     * @var Resources
     */
    private $resources;
    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var ValidatorMetadataFactory|null
     */
    private $validatorMetadataFactory;
    /**
     * @var SerializerClassMetadataFactory|null
     */
    private $serializerClassMetadataFactory;
    /**
     * @var Cache|null
     */
    private $cache;
    /**
     * @var array
     */
    private $loadedClasses = [];

    public function __construct(
        Resources $resources,
        PropertyInfoInterface $propertyInfo,
        ValidatorMetadataFactory $validatorMetadataFactory = null,
        SerializerClassMetadataFactory $serializerClassMetadataFactory = null,
        Cache $cache = null
    ) {
        $this->resources = $resources;
        $this->propertyInfo = $propertyInfo;
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

        $serializerClassMetadata = $this->serializerClassMetadataFactory ? $this->serializerClassMetadataFactory->getMetadataFor($class) : null;
        if ($serializerClassMetadata) {
            $classMetadata->setReflectionClass($serializerClassMetadata->getReflectionClass());
        }

        if ($classReflector = $this->getClassReflector($classMetadata->getReflectionClass())) {
            $classMetadata->setDescription($classReflector->getDocBlock()->getShortDescription());
        }

        $this->loadAttributes(
            $classMetadata,
            $serializerClassMetadata,
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
     * Gets relevant properties for the given groups.
     *
     * @param string[] $normalizationGroups
     * @param string[] $denormalizationGroups
     * @param string[] $validationGroups
     */
    private function loadAttributes(
        ClassMetadata $classMetadata,
        ClassMetadataInterface $serializerClassMetadata = null,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        if ($serializerClassMetadata && null !== $normalizationGroups && null !== $denormalizationGroups) {
            foreach ($serializerClassMetadata->getAttributesMetadata() as $normalizationAttribute) {
                if ('id' === $name = $normalizationAttribute->getName()) {
                    continue;
                }

                if (count(array_intersect($normalizationAttribute->getGroups(), $normalizationGroups))) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, $name, $validationGroups);
                    $attribute->setReadable(true);
                }

                if (count(array_intersect($normalizationAttribute->getGroups(), $denormalizationGroups))) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, $name, $validationGroups);
                    $attribute->setWritable(true);
                }
            }
        } else {
            $reflectionClass = $classMetadata->getReflectionClass();

            // methods
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                if ('getId' === $reflectionMethod->name || 'setId' === $reflectionMethod->name) {
                    continue;
                }

                $numberOfRequiredParameters = $reflectionMethod->getNumberOfRequiredParameters();

                // setters
                if ($numberOfRequiredParameters <= 1 && strpos($reflectionMethod->name, 'set') === 0) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($reflectionMethod->name, 3)), $validationGroups);
                    $attribute->setWritable(true);

                    continue;
                }

                if (0 !== $numberOfRequiredParameters) {
                    continue;
                }

                // getters and hassers
                if (strpos($reflectionMethod->name, 'get') === 0 || strpos($reflectionMethod->name, 'has') === 0) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($reflectionMethod->name, 3)), $validationGroups);
                    $attribute->setReadable(true);

                    continue;
                }

                // issers
                if (strpos($reflectionMethod->name, 'is') === 0) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($reflectionMethod->name, 2)), $validationGroups);
                    $attribute->setReadable(true);
                }
            }

            // properties
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                if ('id' === $reflectionProperty->name) {
                    continue;
                }

                $attribute = $this->getOrCreateAttribute($classMetadata, $reflectionProperty->name, $validationGroups);
                $attribute->setReadable(true);
                $attribute->setWritable(true);
            }
        }
    }

    /**
     * Gets or creates the {@see AttributeMetadata} of the given name.
     *
     * @param ClassMetadata $classMetadata
     * @param string        $attributeName
     * @param string[]|null $validationGroups
     *
     * @return AttributeMetadata
     */
    private function getOrCreateAttribute(ClassMetadata $classMetadata, $attributeName, array $validationGroups = null)
    {
        if (isset($classMetadata->getAttributes()[$attributeName])) {
            return $classMetadata->getAttributes()[$attributeName];
        }

        $attribute = new AttributeMetadata($attributeName);
        $reflectionProperty = $this->getRelfectionProperty($classMetadata->getReflectionClass(), $attributeName);

        if ($reflectionProperty) {
            $attribute->setDescription($this->propertyInfo->getShortDescription($reflectionProperty));

            $types = $this->propertyInfo->getTypes($reflectionProperty);
            $attribute->setTypes($types);

            $type = isset($types[0]) ? $types[0] : null;
            $attribute->setLink(
                $type &&
                (
                    (($class = $type->getClass()) && $this->resources->getResourceForEntity($class)) ||
                    (
                        $type->isCollection() &&
                        $type->getCollectionType() &&
                        ($class = $type->getCollectionType()->getClass()) &&
                        $this->resources->getResourceForEntity($class)
                    )
                )
            );
        }

        if ($this->validatorMetadataFactory) {
            $validatorClassMetadata = $this->validatorMetadataFactory->getMetadataFor($classMetadata->getName());

            foreach ($validatorClassMetadata->getPropertyMetadata($attributeName) as $propertyMetadata) {
                if (null === $validationGroups) {
                    foreach ($propertyMetadata->findConstraints($validatorClassMetadata->getDefaultGroup()) as $constraint) {
                        if ($this->isRequired($constraint)) {
                            $attribute->setRequired(true);

                            break 2;
                        }
                    }
                } else {
                    foreach ($validationGroups as $validationGroup) {
                        foreach ($propertyMetadata->findConstraints($validationGroup) as $constraint) {
                            if ($this->isRequired($constraint)) {
                                $attribute->setRequired(true);

                                break 3;
                            }
                        }
                    }
                }
            }
        }

        $classMetadata->addAttribute($attribute);

        return $attribute;
    }

    /**
     * Is this constraint making the related property required?
     *
     * @param Constraint $constraint
     *
     * @return bool
     */
    private function isRequired(Constraint $constraint)
    {
        foreach (self::$requiredConstraints as $requiredConstraint) {
            if ($constraint instanceof $requiredConstraint) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the ClassReflector associated with this class.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return \phpDocumentor\Reflection\ClassReflector|null
     */
    private function getClassReflector(\ReflectionClass $reflectionClass)
    {
        $className = $reflectionClass->getName();

        if (isset(self::$classReflectors[$className])) {
            return self::$classReflectors[$className];
        }

        if (!($fileName = $reflectionClass->getFileName())) {
            return;
        }

        if (isset(self::$fileReflectors[$fileName])) {
            $fileReflector = self::$fileReflectors[$fileName];
        } else {
            $fileReflector = self::$fileReflectors[$fileName] = new FileReflector($fileName);
            $fileReflector->process();
        }

        foreach ($fileReflector->getClasses() as $classReflector) {
            $className = $classReflector->getName();
            if ('\\' === $className[0]) {
                $className = substr($className, 1);
            }

            if ($className === $reflectionClass->getName()) {
                return self::$classReflectors[$className] = $classReflector;
            }
        }
    }

    /**
     * Gets a class name for a given class or instance.
     *
     * @param mixed $value
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

    /**
     * Gets the {@see |ReflectionProperty} from the class or its parent.
     *
     * @param \ReflectionClass $reflectionClass
     * @param string           $attributeName
     *
     * @return \ReflectionProperty
     */
    private function getRelfectionProperty(\ReflectionClass $reflectionClass, $attributeName)
    {
        if ($reflectionClass->hasProperty($attributeName)) {
            return $reflectionClass->getProperty($attributeName);
        }

        if ($parent = $reflectionClass->getParentClass()) {
            return $this->getRelfectionProperty($parent, $attributeName);
        }
    }
}
