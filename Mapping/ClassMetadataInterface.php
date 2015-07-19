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

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Exception\RuntimeException;

/**
 * Class metadata. Instances are immutable.
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
     * Returns an instance with the specified description.
     *
     * @param string $description
     *
     * @return self
     */
    public function withDescription($description);

    /**
     * Gets the description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Returns an instance with the specified IRI.
     *
     * @param string|null $iri
     *
     * @return self
     */
    public function withIri($iri);

    /**
     * Gets IRI of this class.
     *
     * @return string|null
     */
    public function getIri();

    /**
     * Returns an instance with the specified identifier name.
     *
     * @param string $identifierName
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public function withIdentifierName($identifierName);

    /**
     * Gets the name of the identifier attribute.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function getIdentifierName();

    /**
     * Gets attributes metadata. The key is the attribute name.
     *
     * @return array The attribute name as key, an instance of AttributeMetadataInterface as value.
     */
    public function getAttributesMetadata();

    /**
     * Has the class metadata the given attribute metadata?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttributeMetadata($name);

    /**
     * Gets the given attribute metadata.
     *
     * @param string $name
     *
     * @return AttributeMetadataInterface
     */
    public function getAttributeMetadata($name);

    /**
     * Returns an instance with the specified attribute metadata added.
     * If an attribute with the same name exists it is replaced.
     *
     * @param string                     $attributeName
     * @param AttributeMetadataInterface $attributeMetadata
     *
     * @return self
     */
    public function withAttributeMetadata($attributeName, AttributeMetadataInterface $attributeMetadata);

    /**
     * Returns a {@see \ReflectionClass} instance for this class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass();
}
