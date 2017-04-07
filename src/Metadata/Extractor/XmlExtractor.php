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

namespace ApiPlatform\Core\Metadata\Extractor;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Extracts an array of metadata from a list of XML files.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class XmlExtractor extends AbstractExtractor
{
    const RESOURCE_SCHEMA = __DIR__.'/../schema/metadata.xsd';

    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            $xml = simplexml_import_dom(XmlUtils::loadFile($path, self::RESOURCE_SCHEMA));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($xml->resource as $resource) {
            $resourceClass = (string) $resource['class'];

            $this->resources[$resourceClass] = [
                'shortName' => $this->phpize($resource, 'shortName', 'string'),
                'description' => $this->phpize($resource, 'description', 'string'),
                'iri' => $this->phpize($resource, 'iri', 'string'),
                'itemOperations' => $this->getAttributes($resource, 'itemOperation') ?: null,
                'collectionOperations' => $this->getAttributes($resource, 'collectionOperation') ?: null,
                'attributes' => $this->getAttributes($resource, 'attribute') ?: null,
                'properties' => $this->getProperties($resource) ?: null,
            ];
        }
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
            if (isset($attribute->attribute[0])) {
                $value = $this->getAttributes($attribute, 'attribute');
            } else {
                $value = XmlUtils::phpize($attribute);
            }

            if (isset($attribute['name'])) {
                $attributes[(string) $attribute['name']] = $value;
            } else {
                $attributes[] = $value;
            }
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
                'description' => $this->phpize($property, 'description', 'string'),
                'readable' => $this->phpize($property, 'readable', 'bool'),
                'writable' => $this->phpize($property, 'writable', 'bool'),
                'readableLink' => $this->phpize($property, 'readableLink', 'bool'),
                'writableLink' => $this->phpize($property, 'writableLink', 'bool'),
                'required' => $this->phpize($property, 'required', 'bool'),
                'identifier' => $this->phpize($property, 'identifier', 'bool'),
                'iri' => $this->phpize($property, 'iri', 'string'),
                'attributes' => $this->getAttributes($property, 'attribute'),
            ];
        }

        return $properties;
    }

    /**
     * Transforms an XML attribute's value in a PHP value.
     *
     * @param \SimpleXMLElement $array
     * @param string            $key
     * @param string            $type
     *
     * @return bool|string|null
     */
    private function phpize(\SimpleXMLElement $array, string $key, string $type)
    {
        if (!isset($array[$key])) {
            return;
        }

        switch ($type) {
            case 'string':
                return (string) $array[$key];
            case 'bool':
                return (bool) XmlUtils::phpize($array[$key]);
        }
    }
}
