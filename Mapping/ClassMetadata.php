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

use Symfony\Component\Serializer\Mapping\ClassMetadataInterface as SerializerClassMetadataInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface as ValidatorClassMetadataInterface;

class ClassMetadata
{
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getClassName()} instead.
     */
    public $name;

    /**
     * @var SerializerClassMetadataInterface|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getSerializerClassMetadata()} instead.
     */
    public $serializerClassMetadata;

    /**
     * @var ValidatorClassMetadataInterface|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getValidatorClassMetadata()} instead.
     */
    public $validatorClassMetadata;


    /**
     * @var \ReflectionClass
     */
    private $reflClass;

    /**
     * Constructs a metadata for the given class.
     *
     * @param string                                $class
     * @param SerializerClassMetadataInterface|null $serializerClassMetadata
     * @param ValidatorClassMetadataInterface|null  $validatorClassMetadata
     */
    public function __construct($class, SerializerClassMetadataInterface $serializerClassMetadata = null, ValidatorClassMetadataInterface $validatorClassMetadata = null)
    {
        $this->name = $class;
        $this->serializerClassMetadata = $serializerClassMetadata;
        $this->validatorClassMetadata = $validatorClassMetadata;
    }

    /**
     * Returns the name of the backing PHP class.
     *
     * @return string The name of the backing class.
     */
    public function getClassName()
    {
        return $this->name;
    }

    /**
     * @return SerializerClassMetadataInterface|null
     */
    public function getSerializerClassMetadata()
    {
        return $this->serializerClassMetadata;
    }

    /**
     * @return ValidatorClassMetadataInterface|null
     */
    public function getValidatorClassMetadata()
    {
        return $this->validatorClassMetadata;
    }

    /**
     * Returns a ReflectionClass instance for this class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        if (!$this->reflClass) {
            $this->reflClass = new \ReflectionClass($this->getClassName());
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
        return array(
            'name',
            'serializerClassMetadata',
            'validatorClassMetadata',
        );
    }
}
