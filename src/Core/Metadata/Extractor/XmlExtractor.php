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

use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Extractor\AbstractResourceExtractor;
use ApiPlatform\Metadata\Extractor\PropertyExtractorInterface;
use ApiPlatform\Metadata\Extractor\XmlPropertyExtractor;
use ApiPlatform\Metadata\Extractor\XmlResourceExtractor;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Extracts an array of metadata from a list of XML files.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @deprecated since 2.7, to remove in 3.0 (replaced by ApiPlatform\Metadata\Extractor\XmlExtractor)
 */
final class XmlExtractor extends AbstractResourceExtractor implements PropertyExtractorInterface
{
    public const RESOURCE_SCHEMA = __DIR__.'/../schema/metadata.xsd';

    private $properties;

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        if (null !== $this->properties) {
            return $this->properties;
        }

        $this->properties = [];
        foreach ($this->paths as $path) {
            $this->extractPath($path);
        }

        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            /** @var \SimpleXMLElement $xml */
            $xml = simplexml_import_dom(XmlUtils::loadFile($path, self::RESOURCE_SCHEMA));
        } catch (\InvalidArgumentException $e) {
            // Test if this is a new resource
            try {
                $xml = XmlUtils::loadFile($path, XmlResourceExtractor::SCHEMA);

                return;
            } catch (\InvalidArgumentException $newResourceException) {
                try {
                    $xml = XmlUtils::loadFile($path, XmlPropertyExtractor::SCHEMA);

                    return;
                } catch (\InvalidArgumentException $newPropertyException) {
                    throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        foreach ($xml->resource as $resource) {
            $resourceClass = $this->resolve((string) $resource['class']);

            $this->resources[$resourceClass] = [
                'shortName' => $this->phpizeAttribute($resource, 'shortName', 'string'),
                'description' => $this->phpizeAttribute($resource, 'description', 'string'),
                'iri' => $this->phpizeAttribute($resource, 'iri', 'string'),
                'itemOperations' => $this->extractOperations($resource, 'itemOperation'),
                'collectionOperations' => $this->extractOperations($resource, 'collectionOperation'),
                'subresourceOperations' => $this->extractOperations($resource, 'subresourceOperation'),
                'graphql' => $this->extractOperations($resource, 'operation'),
                'attributes' => $this->extractAttributes($resource, 'attribute') ?: null,
                'properties' => $this->extractProperties($resource) ?: null,
            ];
            $this->properties[$resourceClass] = $this->resources[$resourceClass]['properties'];
        }
    }

    /**
     * Returns the array containing configured operations. Returns NULL if there is no operation configuration.
     */
    private function extractOperations(\SimpleXMLElement $resource, string $operationType): ?array
    {
        $graphql = 'operation' === $operationType;
        if (!$graphql && $legacyOperations = $this->extractAttributes($resource, $operationType)) {
            @trigger_error(
                sprintf('Configuring "%1$s" tags without using a parent "%1$ss" tag is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3', $operationType),
                \E_USER_DEPRECATED
            );

            return $legacyOperations;
        }

        $operationsParent = $graphql ? 'graphql' : "{$operationType}s";
        if (!isset($resource->{$operationsParent})) {
            return null;
        }

        return $this->extractAttributes($resource->{$operationsParent}, $operationType, true);
    }

    /**
     * Recursively transforms an attribute structure into an associative array.
     */
    private function extractAttributes(\SimpleXMLElement $resource, string $elementName, bool $topLevel = false): array
    {
        $attributes = [];
        foreach ($resource->{$elementName} as $attribute) {
            $value = isset($attribute->attribute[0]) ? $this->extractAttributes($attribute, 'attribute') : $this->phpizeContent($attribute);
            // allow empty operations definition, like <collectionOperation name="post" />
            if ($topLevel && '' === $value) {
                $value = [];
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
     */
    private function extractProperties(\SimpleXMLElement $resource): array
    {
        $properties = [];
        foreach ($resource->property as $property) {
            $properties[(string) $property['name']] = [
                'description' => $this->phpizeAttribute($property, 'description', 'string'),
                'readable' => $this->phpizeAttribute($property, 'readable', 'bool'),
                'writable' => $this->phpizeAttribute($property, 'writable', 'bool'),
                'readableLink' => $this->phpizeAttribute($property, 'readableLink', 'bool'),
                'writableLink' => $this->phpizeAttribute($property, 'writableLink', 'bool'),
                'required' => $this->phpizeAttribute($property, 'required', 'bool'),
                'identifier' => $this->phpizeAttribute($property, 'identifier', 'bool'),
                'iri' => $this->phpizeAttribute($property, 'iri', 'string'),
                'attributes' => $this->extractAttributes($property, 'attribute'),
                'subresource' => $property->subresource ? [
                    'collection' => $this->phpizeAttribute($property->subresource, 'collection', 'bool'),
                    'resourceClass' => $this->resolve($this->phpizeAttribute($property->subresource, 'resourceClass', 'string')),
                    'maxDepth' => $this->phpizeAttribute($property->subresource, 'maxDepth', 'integer'),
                ] : null,
            ];
        }

        return $properties;
    }

    /**
     * Transforms an XML attribute's value in a PHP value.
     *
     * @return string|int|bool|null
     */
    private function phpizeAttribute(\SimpleXMLElement $array, string $key, string $type)
    {
        if (!isset($array[$key])) {
            return null;
        }

        switch ($type) {
            case 'string':
                return (string) $array[$key];
            case 'integer':
                return (int) $array[$key];
            case 'bool':
                return (bool) XmlUtils::phpize($array[$key]);
        }

        return null;
    }

    /**
     * Transforms an XML element's content in a PHP value.
     */
    private function phpizeContent(\SimpleXMLElement $array)
    {
        $type = $array['type'] ?? null;
        $value = (string) $array;

        switch ($type) {
            case 'string':
                return $value;
            case 'constant':
                return \constant($value);
            default:
                return XmlUtils::phpize($value);
        }
    }
}
