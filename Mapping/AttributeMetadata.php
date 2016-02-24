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
class AttributeMetadata implements AttributeMetadataInterface
{
    const DEFAULT_IDENTIFIER_NAME = 'id';

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
     *           {@link isNormalizationLink()} instead.
     */
    public $normalizationLink = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isDenormalizationLink()} instead.
     */
    public $denormalizationLink = false;
    /**
     * @var string|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getIri()} instead.
     */
    public $iri;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isIdentifier()} instead.
     */
    public $identifier;

    /**
     * @param string    $name
     * @param bool|null $identifier
     */
    public function __construct($name, $identifier = null)
    {
        $this->name = $name;
        $this->identifier = ($identifier === null) ? $name === self::DEFAULT_IDENTIFIER_NAME : $identifier;
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
    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return $this->types;
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * {@inheritdoc}
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * {@inheritdoc}
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;
    }

    /**
     * {@inheritdoc}
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * {@inheritdoc}
     */
    public function setNormalizationLink($normalizationLink)
    {
        $this->normalizationLink = $normalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isNormalizationLink()
    {
        return $this->normalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function setDenormalizationLink($denormalizationLink)
    {
        $this->denormalizationLink = $denormalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isDenormalizationLink()
    {
        return $this->denormalizationLink;
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
     * @return bool
     */
    public function isIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param bool $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
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
            'normalizationLink',
            'denormalizationLink',
            'iri',
            'identifier',
        ];
    }
}
