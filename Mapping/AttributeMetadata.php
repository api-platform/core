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
 * Attribute metadata.
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
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getLink()} instead.
     */
    public $link = false;
    /**
     * @var string|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getIri()} instead.
     */
    public $iri;

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
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Sets writable.
     *
     * @param bool $writable
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;
    }

    /**
     * Is required?
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Sets required.
     *
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * Set link?
     *
     * @param bool $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Is link?
     *
     * @return bool
     */
    public function isLink()
    {
        return $this->link;
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
            'link',
            'iri',
        ];
    }
}
