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
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getClassName()} instead.
     */
    public $name;
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
     * Constructs a metadata for the given class.
     *
     * @param string                       $class
     * @param SerializerClassMetadata|null $serializerClassMetadata
     * @param ValidatorClassMetadata|null  $validatorClassMetadata
     * @param DoctrineClassMetadata|null   $doctrineClassMetadata
     */
    public function __construct(
        $class,
        SerializerClassMetadata $serializerClassMetadata = null,
        ValidatorClassMetadata $validatorClassMetadata = null,
        DoctrineClassMetadata $doctrineClassMetadata = null
    ) {
        $this->name = $class;
        $this->serializerClassMetadata = $serializerClassMetadata;
        $this->validatorClassMetadata = $validatorClassMetadata;
        $this->doctrineClassMetadata = $doctrineClassMetadata;
    }

    /**
     * Gets relevant properties for the given groups.
     *
     * @param string[] $serializationGroups
     * @param string[] $deserializationGroups
     * @param string[] $validationGroups
     *
     * @return array
     */
    public function getAttributes(
        array $serializationGroups = null,
        array $deserializationGroups = null,
        array $validationGroups = null
    ) {
        $key = serialize([$serializationGroups, $deserializationGroups, $validationGroups]);
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        $attributes = [];
        if ($this->serializerClassMetadata && null !== $serializationGroups && null !== $deserializationGroups) {
            foreach ($this->serializerClassMetadata->getAttributesGroups() as $group => $serializationAttributes) {
                if (in_array($group, $serializationGroups)) {
                    foreach ($serializationAttributes as $attributeName) {
                        $attributes[$attributeName]['readable'] = true;
                    }
                }

                if (in_array($group, $deserializationGroups)) {
                    foreach ($serializationAttributes as $attributeName) {
                        $attributes[$attributeName]['writeable'] = true;
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
                if (
                    !$reflMethod->isConstructor() &&
                    !$reflMethod->isDestructor() &&
                    0 === $reflMethod->getNumberOfRequiredParameters()
                ) {
                    $methodName = $reflMethod->getName();

                    if (strpos($methodName, 'get') === 0 || strpos($methodName, 'has') === 0) {
                        // getters and hassers
                        $attributeName = lcfirst(substr($methodName, 3));
                    } elseif (strpos($methodName, 'is') === 0) {
                        // issers
                        $attributeName = lcfirst(substr($methodName, 2));
                    }

                    if (isset($attributeName)) {
                        $attributes[$attributeName] = ['readable' => true, 'writeable' => true];
                        $this->populateAttribute($attributeName, $attributes[$attributeName], $validationGroups);
                        unset($attributeName);
                    }
                }
            }

            // properties
            foreach ($reflClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflProperty) {
                $attributeName = $reflProperty->getName();
                if (!isset($attributes[$attributeName])) {
                    $attributes[$attributeName] = ['readable' => true, 'writeable' => true];
                    $this->populateAttribute($attributeName, $attributes[$attributeName], $validationGroups);
                }
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

        if (!isset($attribute['writeable'])) {
            $attribute['writeable'] = false;
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
    }

    /**
     * Is this constraint making the related property required?
     *
     * @param  Constraint $constraint
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
}
