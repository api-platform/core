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
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadata implements ClassMetadataInterface
{
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getClassName()} instead.
     */
    public $name;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getDescription()} instead.
     */
    public $description;
    /**
     * @var string|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getIri()} instead.
     */
    public $iri;
    /**
     * @var AttributeMetadataInterface[]
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getAttributesMetadata()} instead.
     */
    public $attributesMetadata = [];
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getIdentifierName()} instead.
     */
    public $identifierName;
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * Constructs a metadata for the given class.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
    public function withDescription($description)
    {
        $classMetadata = clone $this;
        $classMetadata->description = $description;

        return $classMetadata;
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
    public function withIri($iri)
    {
        $classMetadata = clone $this;
        $classMetadata->iri = $iri;

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * {@inheritdoc}
     */
    public function withIdentifierName($identifierName)
    {
        if (!isset($this->attributesMetadata[$identifierName])) {
            throw new InvalidArgumentException(
                sprintf('The attribute "%s" cannot be the identifier: this attribute does not exist.', $identifierName)
            );
        }

        $classMetadata = clone $this;
        $classMetadata->identifierName = $identifierName;

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierName()
    {
        if (!$this->identifierName) {
            throw new RuntimeException(
                sprintf(
                    'The class "%s" has no identifier. Maybe you forgot to define the Entity Identifier, or using composite identifiers (which are not supported)?',
                    $this->name));
        }

        return $this->identifierName;
    }

    /**
     * {@inheritdoc}
     */
    public function withAttributeMetadata($name, AttributeMetadataInterface $attributeMetadata)
    {
        $classMetadata = clone $this;
        $classMetadata->attributesMetadata[$name] = $attributeMetadata;

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributesMetadata()
    {
        return $this->attributesMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttributeMetadata($name)
    {
        return isset($this->attributesMetadata[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeMetadata($name)
    {
        return $this->attributesMetadata[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionClass()
    {
        if (!$this->reflectionClass) {
            return $this->reflectionClass = new \ReflectionClass($this->name);
        }

        return $this->reflectionClass;
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
            'description',
            'iri',
            'identifierName',
            'attributesMetadata',
        ];
    }
}
