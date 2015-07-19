<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping;

use PropertyInfo\Type;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadata implements AttributeMetadataInterface
{
    /**
     * @var Type|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getType()} instead.
     */
    public $type;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getDescription()} instead.
     */
    public $description = '';
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
     *           {@link isLink()} instead.
     */
    public $link = false;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getLinkClass()} instead.
     */
    public $linkClass = '';
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
     * {@inheritdoc}
     */
    public function withType(Type $type)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->type = $type;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
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
    public function withDescription($description)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->description = $description;

        return $attributeMetadata;
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
    public function withReadable($readable)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->readable = $readable;

        return $attributeMetadata;
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
    public function withWritable($writable)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->writable = $writable;

        return $attributeMetadata;
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
    public function withRequired($required)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->required = $required;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function withLink($link)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->link = $link;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function isLink()
    {
        return $this->link;
    }

    /**
     * {@inheritdoc}
     */
    public function withLinkClass($linkClass)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->linkClass = $linkClass;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkClass()
    {
        return $this->linkClass;
    }

    /**
     * {@inheritdoc}
     */
    public function withNormalizationLink($normalizationLink)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->normalizationLink = $normalizationLink;

        return $attributeMetadata;
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
    public function withDenormalizationLink($denormalizationLink)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->denormalizationLink = $denormalizationLink;

        return $attributeMetadata;
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
    public function withIri($iri)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->iri = $iri;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
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
            'type',
            'description',
            'readable',
            'writable',
            'required',
            'link',
            'linkClass',
            'normalizationLink',
            'denormalizationLink',
            'iri',
        ];
    }
}
