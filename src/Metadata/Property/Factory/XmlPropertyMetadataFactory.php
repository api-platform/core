<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Creates a property metadata from XML {@see Property} configuration.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class XmlPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    const RESOURCE_SCHEMA = __DIR__.'/../../schema/metadata.xsd';

    private $paths;
    private $decorated;

    /**
     * @param string[]                              $paths
     * @param PropertyMetadataFactoryInterface|null $decorated
     */
    public function __construct(array $paths, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : PropertyMetadata
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        if (
            !property_exists($resourceClass, $property) ||
            empty($propertyMetadata = $this->getMetadata($resourceClass, $property))
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->update($parentPropertyMetadata, $propertyMetadata);
        }

        return new PropertyMetadata(
            null,
            $propertyMetadata['description'],
            $propertyMetadata['readable'],
            $propertyMetadata['writable'],
            $propertyMetadata['readableLink'],
            $propertyMetadata['writableLink'],
            $propertyMetadata['required'],
            $propertyMetadata['identifier'],
            $propertyMetadata['iri'],
            null,
            $propertyMetadata['attributes']
        );
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param PropertyMetadata|null $parentPropertyMetadata
     * @param string                $resourceClass
     * @param string                $property
     *
     * @throws PropertyNotFoundException
     *
     * @return PropertyMetadata
     */
    private function handleNotFound(PropertyMetadata $parentPropertyMetadata = null, string $resourceClass, string $property) : PropertyMetadata
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Extracts metadata from the XML tree.
     *
     * @param string $resourceClass
     * @param string $propertyName
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    private function getMetadata(string $resourceClass, string $propertyName) : array
    {
        foreach ($this->paths as $path) {
            try {
                $domDocument = XmlUtils::loadFile($path, self::RESOURCE_SCHEMA);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            $properties = (new \DOMXPath($domDocument))->query(sprintf('//resources/resource[@class="%s"]/property[@name="%s"]', $resourceClass, $propertyName));

            if (
                false === $properties ||
                0 >= $properties->length ||
                null === $properties->item(0) ||
                false === $property = simplexml_import_dom($properties->item(0))
            ) {
                continue;
            }

            return [
                'description' => (string) $property['description'] ?: null,
                'readable' => $property['readable'] ? (bool) XmlUtils::phpize($property['readable']) : null,
                'writable' => $property['writable'] ? (bool) XmlUtils::phpize($property['writable']) : null,
                'readableLink' => $property['readableLink'] ? (bool) XmlUtils::phpize($property['readableLink']) : null,
                'writableLink' => $property['writableLink'] ? (bool) XmlUtils::phpize($property['writableLink']) : null,
                'required' => $property['required'] ? (bool) XmlUtils::phpize($property['required']) : null,
                'identifier' => $property['identifier'] ? (bool) XmlUtils::phpize($property['identifier']) : null,
                'iri' => (string) $property['iri'] ?: null,
                'attributes' => $this->getAttributes($property),
            ];
        }

        return [];
    }

    /**
     * Recursively transforms an attribute structure into an associative array.
     *
     * @param \SimpleXMLElement $element
     *
     * @return array
     */
    private function getAttributes(\SimpleXMLElement $element) : array
    {
        $attributes = [];
        foreach ($element->attribute as $attribute) {
            $value = isset($attribute->attribute[0]) ? $this->getAttributes($attribute) : (string) $attribute;

            if (isset($attribute['name'])) {
                $attributes[(string) $attribute['name']] = $value;
            } else {
                $attributes[] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param array            $metadata
     *
     * @return PropertyMetadata
     */
    private function update(PropertyMetadata $propertyMetadata, array $metadata) : PropertyMetadata
    {
        $metadataAccessors = [
            'description' => 'get',
            'readable' => 'is',
            'writable' => 'is',
            'writableLink' => 'is',
            'readableLink' => 'is',
            'required' => 'is',
            'identifier' => 'is',
            'iri' => 'get',
            'attributes' => 'get',
        ];

        foreach ($metadataAccessors as $metadataKey => $accessorPrefix) {
            if (null === $metadata[$metadataKey] || null !== $propertyMetadata->{$accessorPrefix.ucfirst($metadataKey)}()) {
                continue;
            }

            $propertyMetadata = $propertyMetadata->{'with'.ucfirst($metadataKey)}($metadata[$metadataKey]);
        }

        return $propertyMetadata;
    }
}
