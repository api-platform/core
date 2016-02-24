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
 * Class metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ClassMetadataInterface
{
    /**
     * Gets the class name.
     *
     * @return string
     */
    public function getName();

    /**
     * Sets description.
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Gets the description.
     *
     * @return string
     */
    public function getDescription();

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
     * Adds an {@link AttributeMetadata}.
     *
     * @param AttributeMetadata $attributeMetadata
     */
    public function addAttribute(AttributeMetadata $attributeMetadata);

    /**
     * Gets attributes.
     *
     * @return AttributeMetadata[]
     */
    public function getAttributes();

    /**
     * Returns a {@see \ReflectionClass} instance for this class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass();

    /**
     * Gets the attribute identifier of the class.
     *
     * @return AttributeMetadataInterface
     */
    public function getIdentifier();
}
