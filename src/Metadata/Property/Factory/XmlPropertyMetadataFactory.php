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
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Creates a property metadata from XML {@see Property} configuration.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class XmlPropertyMetadataFactory extends AbstractFilePropertyMetadataFactory
{
    const RESOURCE_SCHEMA = __DIR__.'/../../schema/metadata.xsd';

    /**
     * {@inheritdoc}
     */
    protected function getMetadata(string $resourceClass, string $propertyName): array
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
    private function getAttributes(\SimpleXMLElement $element): array
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
}
