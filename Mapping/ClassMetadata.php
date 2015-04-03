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

/**
 * Class metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
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
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getDescription()} instead.
     */
    public $description;
    /**
     * @var string|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getIri()} instead.
     */
    public $iri;
    /**
     * @var AttributeMetadata[]
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getAttributes()} instead.
     */
    public $attributes = [];
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * Constructs a metadata for the given class.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the class name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Gets the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets IRI of this attribute.
     *
     * @param string $iri
     */
    public function setIri($iri)
    {
        $this->iri = $iri;
    }

    /**
     * Gets IRI of this attribute.
     *
     * @return string|null
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * Adds an {@link AttributeMetadata}.
     *
     * @param AttributeMetadata $attributeMetadata
     */
    public function addAttribute(AttributeMetadata $attributeMetadata)
    {
        $this->attributes[$attributeMetadata->getName()] = $attributeMetadata;
    }

    /**
     * Gets attributes.
     *
     * @return AttributeMetadata[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns a {@see \ReflectionClass} instance for this class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        if (!$this->reflectionClass) {
            return $this->reflectionClass = new \ReflectionClass($this->name);
        }

        return $this->reflectionClass;
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
            'iri',
            'attributes',
        ];
    }
}
