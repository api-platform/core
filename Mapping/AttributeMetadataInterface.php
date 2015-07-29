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
 * Attribute metadata. Instances are immutable.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface AttributeMetadataInterface
{
    /**
     * Returns an instance with the specified type.
     *
     * @param Type $type
     *
     * @return self
     */
    public function withType(Type $type);

    /**
     * Gets type.
     *
     * @return Type
     */
    public function getType();

    /**
     * Gets description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Returns an instance with the specified description.
     *
     * @param string $description
     *
     * @return self
     */
    public function withDescription($description);

    /**
     * Is readable?
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Returns an instance with the specified readable flag.
     *
     * @param bool $readable
     *
     * @return self
     */
    public function withReadable($readable);

    /**
     * Is writable?
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Returns an instance with the specified writable flag.
     *
     * @param bool $writable
     *
     * @return self
     */
    public function withWritable($writable);

    /**
     * Is required?
     *
     * @return bool
     */
    public function isRequired();

    /**
     * Returns an instance with the specified required flag.
     *
     * @param bool $required
     *
     * @return self
     */
    public function withRequired($required);

    /**
     * Returns an instance with the specified link value.
     *
     * @param bool $link
     *
     * @return self
     */
    public function withLink($link);

    /**
     * Is this attribute a relation to a resource?
     *
     * @return bool
     */
    public function isLink();

    /**
     * Returns an instance with the specified link class.
     *
     * @param string $linkClass
     *
     * @return self
     */
    public function withLinkClass($linkClass);

    /**
     * Gets the entity class of the related resource.
     *
     * @return string
     */
    public function getLinkClass();

    /**
     * Returns an instance with the specified normalization link flag.
     *
     * @param bool $normalizationLink
     *
     * @return self
     */
    public function withNormalizationLink($normalizationLink);

    /**
     * Is normalization link?
     *
     * @return bool
     */
    public function isNormalizationLink();

    /**
     * Returns an instance with the specified denormalization link flag.
     *
     * @param bool $denormalizationLink
     *
     * @return self
     */
    public function withDenormalizationLink($denormalizationLink);

    /**
     * Is denormalization link?
     *
     * @return bool
     */
    public function isDenormalizationLink();

    /**
     * Returns an instance with the specified IRI.
     *
     * @param string $iri
     *
     * @return self
     */
    public function withIri($iri);

    /**
     * Gets IRI of this attribute.
     *
     * @return string|null
     */
    public function getIri();
}
