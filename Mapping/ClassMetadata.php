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

use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\FileReflector;
use PropertyInfo\PropertyInfoInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadata as SerializerClassMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface as ValidatorClassMetadata;

class ClassMetadata
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
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getClassName()} instead.
     */
    public $name;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getDescription()} instead.
     */
    public $description;
    /**
     * @var array
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getAttributes()} instead.
     */
    public $attributes;
    /**
     * @var SerializerClassMetadata|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it.
     */
    public $serializerClassMetadata;
    /**
     * @var ValidatorClassMetadata|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it.
     */
    public $validatorClassMetadata;
    /**
     * @var DoctrineClassMetadataInterface|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it.
     */
    public $doctrineClassMetadata;
    /**
     * @var \ReflectionClass
     */
    private $reflClass;
    /**
     * @var \phpDocumentor\Reflection\ClassReflector
     */
    private $classReflector;
    /**
     * @var DocBlock
     */
    private $docBlock;

    /**
     * Constructs a metadata for the given class.
     *
     * @param string                       $class
     * @param PropertyInfoInterface        $propertyInfo
     * @param SerializerClassMetadata|null $serializerClassMetadata
     * @param ValidatorClassMetadata|null  $validatorClassMetadata
     * @param DoctrineClassMetadata|null   $doctrineClassMetadata
     */
    public function __construct(
        $class,
        PropertyInfoInterface $propertyInfo,
        SerializerClassMetadata $serializerClassMetadata = null,
        ValidatorClassMetadata $validatorClassMetadata = null,
        DoctrineClassMetadata $doctrineClassMetadata = null
    ) {
        $this->name = $class;
        $this->propertyInfo = $propertyInfo;
        $this->serializerClassMetadata = $serializerClassMetadata;
        $this->validatorClassMetadata = $validatorClassMetadata;
        $this->doctrineClassMetadata = $doctrineClassMetadata;
    }

    /**
     * Gets the description of this class coming from the PHPDoc.
     *
     * @return string
     */
    public function getDescription()
    {
        if (null === $this->description) {
            $this->description = $this->getClassReflector()->getDocBlock()->getShortDescription();
        }

        return $this->description;
    }

    /**
     * Gets relevant properties for the given groups.
     *
     * @param string[] $normalizationGroups
     * @param string[] $denormalizationGroups
     * @param string[] $validationGroups
     *
     * @return array
     */
    public function getAttributes(
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $key = serialize([$normalizationGroups, $denormalizationGroups, $validationGroups]);
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        $attributes = [];
        if ($this->serializerClassMetadata && null !== $normalizationGroups && null !== $denormalizationGroups) {
            foreach ($this->serializerClassMetadata->getAttributesGroups() as $group => $normalizationAttributes) {
                if (in_array($group, $normalizationGroups)) {
                    foreach ($normalizationAttributes as $attributeName) {
                        $attributes[$attributeName]['readable'] = true;
                    }
                }

                if (in_array($group, $denormalizationGroups)) {
                    foreach ($normalizationAttributes as $attributeName) {
                        $attributes[$attributeName]['writable'] = true;
                    }
                }

                if (isset($attributeName)) {
                    $this->populateAttribute($attributeName, $attributes[$attributeName], $validationGroups);
                    unset($attributeName);
                }
            }
        } else {
            $reflClass = $this->getReflectionClass();

            // methods
            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflMethod) {
                $methodName = $reflMethod->name;
                $numberOfRequiredParameters = $reflMethod->getNumberOfRequiredParameters();

                // setters
                if (1 === $numberOfRequiredParameters && strpos($methodName, 'set') === 0) {
                    $attributes[lcfirst(substr($methodName, 3))]['writable'] = true;

                    continue;
                }

                if (0 !== $numberOfRequiredParameters) {
                    continue;
                }

                // getters and hassers
                if (strpos($methodName, 'get') === 0 || strpos($methodName, 'has') === 0) {
                    $attributes[lcfirst(substr($methodName, 3))]['readable'] = true;

                    continue;
                }

                // issers
                if (strpos($methodName, 'is') === 0) {
                    $attributes[lcfirst(substr($methodName, 2))]['readable'] = true;
                }
            }

            // properties
            foreach ($reflClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflProperty) {
                $attributes[$reflProperty->name] = [
                    'readable' => true,
                    'writable' => true,
                ];
            }

            foreach ($attributes as $attributeName => &$data) {
                $this->populateAttribute($attributeName, $data, $validationGroups);
            }
        }

        return $this->attributes[$key] = $attributes;
    }

    /**
     * Returns a ReflectionClass instance for this class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        if (!$this->reflClass) {
            if ($this->serializerClassMetadata) {
                return $this->reflClass = $this->serializerClassMetadata->getReflectionClass();
            }

            if ($this->doctrineClassMetadata) {
                return $this->reflClass = $this->doctrineClassMetadata->getReflectionClass();
            }

            return $this->reflClass = new \ReflectionClass($this->getClassName());
        }

        return $this->reflClass;
    }

    /**
     * Returns the names of the properties that should be serialized.
     *
     * @return string[]
     */
    public function __sleep()
    {
        return [
            'name',
            'description',
            'attributes',
            'serializerClassMetadata',
            'validatorClassMetadata',
            'doctrineClassMetadata',
        ];
    }

    /**
     * Populates an attribute.
     *
     * @param string     $name
     * @param array      $attribute
     * @param array|null $validationGroups
     */
    private function populateAttribute($name, array &$attribute, array $validationGroups = null)
    {
        if (!isset($attribute['readable'])) {
            $attribute['readable'] = false;
        }

        if (!isset($attribute['writable'])) {
            $attribute['writable'] = false;
        }

        foreach ($this->validatorClassMetadata->getPropertyMetadata($name) as $propertyMetadata) {
            if (null === $validationGroups) {
                foreach ($propertyMetadata->findConstraints($this->validatorClassMetadata->getDefaultGroup()) as $constraint) {
                    if ($this->isRequired($constraint)) {
                        $attribute['required'] = true;

                        break 2;
                    }
                }
            } else {
                foreach ($validationGroups as $validationGroup) {
                    foreach ($propertyMetadata->findConstraints($validationGroup) as $constraint) {
                        if ($this->isRequired($constraint)) {
                            $attribute['required'] = true;

                            break 3;
                        }
                    }
                }
            }
        }

        if (!isset($attribute['required'])) {
            $attribute['required'] = false;
        }

        if ($this->doctrineClassMetadata && $this->doctrineClassMetadata->hasAssociation($name)) {
            $attribute['type'] = $this->doctrineClassMetadata->getAssociationTargetClass($name);
        } else {
            $attribute['type'] = null;
        }

        $reflClass = $this->getReflectionClass();
        if ($reflClass->hasProperty($name)) {
            $attribute['description'] = $this->propertyInfo->getShortDescription($reflClass->getProperty($name));
        }
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
     * @return \phpDocumentor\Reflection\ClassReflector
     */
    private function getClassReflector()
    {
        if ($this->classReflector) {
            return $this->classReflector;
        }

        $reflectionClass = $this->getReflectionClass();

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
                return $this->classReflector = $classReflector;
            }
        }
    }
}
