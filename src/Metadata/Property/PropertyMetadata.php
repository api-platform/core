<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property;

use Symfony\Component\PropertyInfo\Type;

/**
 * Property metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyMetadata
{
    private $type;
    private $description;
    private $readable;
    private $writable;
    private $readableLink;
    private $writableLink;
    private $required;
    private $iri;
    private $identifier;
    private $attributes;

    public function __construct(Type $type = null, string $description = null, bool $readable = null, bool $writable = null, bool $readableLink = null, bool $writableLink = null, bool $required = null, bool $identifier = null, string $iri = null, array $attributes = [])
    {
        $this->type = $type;
        $this->description = $description;
        $this->readable = $readable;
        $this->writable = $writable;
        $this->readableLink = $readableLink;
        $this->writableLink = $writableLink;
        $this->required = $required;
        $this->identifier = $identifier;
        $this->iri = $iri;
        $this->attributes = $attributes;
    }

    /**
     * Gets type.
     *
     * @return Type|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns a new instance with the given type.
     *
     * @param Type $type
     *
     * @return PropertyMetadata
     */
    public function withType(Type $type) : self
    {
        $metadata = clone $this;
        $metadata->type = $type;

        return $metadata;
    }

    /**
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns a new instance with the given description.
     *
     * @param string $description
     *
     * @return PropertyMetadata
     */
    public function withDescription($description) : self
    {
        $metadata = clone $this;
        $metadata->description = $description;

        return $metadata;
    }

    /**
     * Is readable?
     *
     * @return bool|null
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Returns a new instance of Metadata with the given readable flag.
     *
     * @param bool $readable
     *
     * @return PropertyMetadata
     */
    public function withReadable(bool $readable) : self
    {
        $metadata = clone $this;
        $metadata->readable = $readable;

        return $metadata;
    }

    /**
     * Is writable?
     *
     * @return bool|null
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Returns a new instance with the given writable flag.
     *
     * @param bool $writable
     *
     * @return PropertyMetadata
     */
    public function withWritable(bool $writable) : self
    {
        $metadata = clone $this;
        $metadata->writable = $writable;

        return $metadata;
    }

    /**
     * Is required?
     *
     * @return bool|null
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Returns a new instance with the given required flag.
     *
     * @param bool $required
     *
     * @return PropertyMetadata
     */
    public function withRequired(bool $required) : self
    {
        $metadata = clone $this;
        $metadata->required = $required;

        return $metadata;
    }

    /**
     * Should an IRI or an object be provided in write context?
     *
     * @return bool|null
     */
    public function isWritableLink()
    {
        return $this->writableLink;
    }

    /**
     * Returns a new instance with the given writable link flag.
     *
     * @param bool $writableLink
     *
     * @return PropertyMetadata
     */
    public function withWritableLink(bool $writableLink) : self
    {
        $metadata = clone $this;
        $metadata->writableLink = $writableLink;

        return $metadata;
    }

    /**
     * Is an IRI or an object generated in read context?
     *
     * @return bool|null
     */
    public function isReadableLink()
    {
        return $this->readableLink;
    }

    /**
     * Returns a new instance with the given readable link flag.
     *
     * @param bool $readableLink
     *
     * @return PropertyMetadata
     */
    public function withReadableLink(bool $readableLink) : self
    {
        $metadata = clone $this;
        $metadata->readableLink = $readableLink;

        return $metadata;
    }

    /**
     * Gets IRI of this property.
     *
     * @return string|null
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * Returns a new instance with the given IRI.
     *
     * @param string|null $iri
     *
     * @return PropertyMetadata
     */
    public function withIri(string $iri = null) : self
    {
        $metadata = clone $this;
        $metadata->iri = $iri;

        return $metadata;
    }

    /**
     * Is this attribute an identifier?
     *
     * @return bool|null
     */
    public function isIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns a new instance with the given identifier flag.
     *
     * @param bool $identifier
     *
     * @return PropertyMetadata
     */
    public function withIdentifier(bool $identifier) : self
    {
        $metadata = clone $this;
        $metadata->identifier = $identifier;

        return $metadata;
    }

    /**
     * Gets attributes.
     *
     * @return array
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Returns a new instance with the given attribute.
     *
     * @param array $attributes
     *
     * @return PropertyMetadata
     */
    public function withAttributes(array $attributes) : self
    {
        $metadata = clone $this;
        $metadata->attributes = $attributes;

        return $metadata;
    }
}
