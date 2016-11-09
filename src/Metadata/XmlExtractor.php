<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Converts a list of XML metadata files in a PHP array.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class XmlExtractor
{
    const RESOURCE_SCHEMA = __DIR__.'/schema/metadata.xsd';

    private $paths;
    private $resources;

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Parses all metadata files and convert them in an array.
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getResources(): array
    {
        if (null !== $this->resources) {
            return $this->resources;
        }

        $this->resources = [];
        foreach ($this->paths as $path) {
            try {
                $xml = simplexml_import_dom(XmlUtils::loadFile($path, self::RESOURCE_SCHEMA));
            } catch (\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            foreach ($xml->resource as $resource) {
                $resourceClass = (string) $resource['class'];

                $this->resources[$resourceClass] = [
                    'shortName' => (string) $resource['shortName'] ?: null,
                    'description' => (string) $resource['description'] ?: null,
                    'iri' => (string) $resource['iri'] ?: null,
                    'itemOperations' => $this->getAttributes($resource, 'itemOperation') ?: null,
                    'collectionOperations' => $this->getAttributes($resource, 'collectionOperation') ?: null,
                    'attributes' => $this->getAttributes($resource, 'attribute') ?: null,
                    'properties' => $this->getProperties($resource, $resourceClass) ?: null,
                ];
            }
        }

        return $this->resources;
    }

    /**
     * Recursively transforms an attribute structure into an associative array.
     *
     * @param \SimpleXMLElement $resource
     * @param string            $elementName
     *
     * @return array
     */
    private function getAttributes(\SimpleXMLElement $resource, string $elementName): array
    {
        $attributes = [];
        foreach ($resource->$elementName as $attribute) {
            $value = isset($attribute->attribute[0]) ? $this->getAttributes($attribute, 'attribute') : (string) $attribute;
            isset($attribute['name']) ? $attributes[(string) $attribute['name']] = $value : $attributes[] = $value;
        }

        return $attributes;
    }

    /**
     * Gets metadata of a property.
     *
     * @param \SimpleXMLElement $resource
     *
     * @return array
     */
    private function getProperties(\SimpleXMLElement $resource): array
    {
        $properties = [];
        foreach ($resource->property as $property) {
            $properties[(string) $property['name']] = [
                'description' => (string) $property['description'] ?: null,
                'readable' => $property['readable'] ? (bool) XmlUtils::phpize($property['readable']) : null,
                'writable' => $property['writable'] ? (bool) XmlUtils::phpize($property['writable']) : null,
                'readableLink' => $property['readableLink'] ? (bool) XmlUtils::phpize($property['readableLink']) : null,
                'writableLink' => $property['writableLink'] ? (bool) XmlUtils::phpize($property['writableLink']) : null,
                'required' => $property['required'] ? (bool) XmlUtils::phpize($property['required']) : null,
                'identifier' => $property['identifier'] ? (bool) XmlUtils::phpize($property['identifier']) : null,
                'iri' => (string) $property['iri'] ?: null,
                'attributes' => $this->getAttributes($property, 'attribute'),
            ];
        }

        return $properties;
    }
}
