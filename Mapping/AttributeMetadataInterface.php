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
 * Attribute metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface AttributeMetadataInterface
{
    /**
     * Gets name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set types.
     *
     * @param \PropertyInfo\Type[] $types
     */
    public function setTypes(array $types);

    /**
     * Gets types.
     *
     * @return \PropertyInfo\Type[]
     */
    public function getTypes();

    /**
     * Gets description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Sets description.
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Is readable?
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Sets readable.
     *
     * @param bool $readable
     */
    public function setReadable($readable);

    /**
     * Is writable?
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Sets writable.
     *
     * @param bool $writable
     */
    public function setWritable($writable);

    /**
     * Is required?
     *
     * @return bool
     */
    public function isRequired();

    /**
     * Sets required.
     *
     * @param bool $required
     */
    public function setRequired($required);

    /**
     * Sets normalization link?
     *
     * @param bool normalizationLink
     */
    public function setNormalizationLink($normalizationLink);

    /**
     * Is normalization link?
     *
     * @return bool
     */
    public function isNormalizationLink();

    /**
     * Sets denormalization link?
     *
     * @param bool normalizationLink
     */
    public function setDenormalizationLink($denormalizationLink);

    /**
     * Is denormalization link?
     *
     * @return bool
     */
    public function isDenormalizationLink();

    /**
     * Sets IRI of this attribute.
     *
     * @param string $iri
     */
    public function setIri($iri);

    /**
     * Gets IRI of this attribute.
     *
     * @return string|null
     */
    public function getIri();

    /**
     * Is attribute the identifier of the class.
     *
     * @return bool
     */
    public function isIdentifier();
}
