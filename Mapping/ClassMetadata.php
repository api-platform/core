<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadata implements ClassMetadataInterface
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setIri($iri)
    {
        $this->iri = $iri;
    }

    /**
     * {@inheritdoc}
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * @return AttributeMetadataInterface
     */
    public function getIdentifier()
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->isIdentifier()) {
                return $attribute;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addAttribute(AttributeMetadata $attributeMetadata)
    {
        $this->attributes[$attributeMetadata->getName()] = $attributeMetadata;
    }

    /**
     * @param AttributeMetadata $attributeMetadata
     */
    public function removeAttribute(AttributeMetadata $attributeMetadata)
    {
        if (isset($this->attributes[$attributeMetadata->getName()])) {
            unset($this->attributes[$attributeMetadata->getName()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
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
