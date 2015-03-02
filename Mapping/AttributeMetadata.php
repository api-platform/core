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
 * AttributeMetadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadata
{
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getName()} instead.
     */
    public $name;
    /**
     * @var \PropertyInfo\Type[]
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getTypes()} instead.
     */
    public $types;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getDescription()} instead.
     */
    public $description;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isReadable()} instead.
     */
    public $readable = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isWritable()} instead.
     */
    public $writable = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isRequired()} instead.
     */
    public $required = false;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set types.
     *
     * @param \PropertyInfo\Type[] $types
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    /**
     * Gets types.
     *
     * @return \PropertyInfo\Type[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Gets description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * Is readable?
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Sets readable.
     *
     * @param bool $readable
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;
    }

    /**
     * Is writable?
     *
     * @return boolean
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Sets writable.
     *
     * @param boolean $writable
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;
    }

    /**
     * Is required?
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Sets required.
     *
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
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
            'types',
            'description',
            'readable',
            'writable',
            'required',
        ];
    }
}
