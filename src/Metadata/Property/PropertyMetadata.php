<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
    private $childInherited;
    private $attributes;

    public function __construct(Type $type = null, string $description = null, bool $readable = null, bool $writable = null, bool $readableLink = null, bool $writableLink = null, bool $required = null, bool $identifier = null, string $iri = null, $childInherited = null, array $attributes = null)
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
        $this->childInherited = $childInherited;
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
     * @return self
     */
    public function withType(Type $type): self
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
     * @return self
     */
    public function withDescription($description): self
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
     * @return self
     */
    public function withReadable(bool $readable): self
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
     * @return self
     */
    public function withWritable(bool $writable): self
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
        if (true === $this->required && false === $this->writable) {
            return false;
        }

        return $this->required;
    }

    /**
     * Returns a new instance with the given required flag.
     *
     * @param bool $required
     *
     * @return self
     */
    public function withRequired(bool $required): self
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
     * @return self
     */
    public function withWritableLink(bool $writableLink): self
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
     * @return self
     */
    public function withReadableLink(bool $readableLink): self
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
     * @return self
     */
    public function withIri(string $iri = null): self
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
     * @return self
     */
    public function withIdentifier(bool $identifier): self
    {
        $metadata = clone $this;
        $metadata->identifier = $identifier;

        return $metadata;
    }

    /**
     * Gets attributes.
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns a new instance with the given attribute.
     *
     * @param array $attributes
     *
     * @return self
     */
    public function withAttributes(array $attributes): self
    {
        $metadata = clone $this;
        $metadata->attributes = $attributes;

        return $metadata;
    }

    /**
     * Is the property inherited from a child class?
     *
     * @return string|null
     */
    public function isChildInherited()
    {
        return $this->childInherited;
    }

    /**
     * Returns a new instance with the given child inherited class.
     *
     * @param string $childInherited
     *
     * @return self
     */
    public function withChildInherited(string $childInherited): self
    {
        $metadata = clone $this;
        $metadata->childInherited = $childInherited;

        return $metadata;
    }
}
