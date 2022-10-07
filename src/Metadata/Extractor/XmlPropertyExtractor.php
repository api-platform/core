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

namespace ApiPlatform\Metadata\Extractor;

use ApiPlatform\Exception\InvalidArgumentException;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Extracts an array of metadata from a list of XML files.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class XmlPropertyExtractor extends AbstractPropertyExtractor
{
    public const SCHEMA = __DIR__.'/schema/properties.xsd';

    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            /** @var \SimpleXMLElement $xml */
            $xml = simplexml_import_dom(XmlUtils::loadFile($path, self::SCHEMA));
        } catch (\InvalidArgumentException $e) {
            // Ensure it's not a resource
            try {
                simplexml_import_dom(XmlUtils::loadFile($path, XmlResourceExtractor::SCHEMA));
            } catch (\InvalidArgumentException $error) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            // It's a resource: ignore error
            return;
        }

        foreach ($xml->property as $property) {
            $this->properties[$this->resolve((string) $property['resource'])][(string) $property['name']] = [
                'description' => $this->phpize($property, 'description', 'string'),
                'readable' => $this->phpize($property, 'readable', 'bool'),
                'writable' => $this->phpize($property, 'writable', 'bool'),
                'readableLink' => $this->phpize($property, 'readableLink', 'bool'),
                'writableLink' => $this->phpize($property, 'writableLink', 'bool'),
                'required' => $this->phpize($property, 'required', 'bool'),
                'identifier' => $this->phpize($property, 'identifier', 'bool'),
                'default' => $this->phpize($property, 'default', 'string'),
                'example' => $this->phpize($property, 'example', 'string'),
                'deprecationReason' => $this->phpize($property, 'deprecationReason', 'string'),
                'fetchable' => $this->phpize($property, 'fetchable', 'bool'),
                'fetchEager' => $this->phpize($property, 'fetchEager', 'bool'),
                'jsonldContext' => isset($property->jsonldContext->values) ? $this->buildValues($property->jsonldContext->values) : null,
                'openapiContext' => isset($property->openapiContext->values) ? $this->buildValues($property->openapiContext->values) : null,
                'jsonSchemaContext' => isset($property->jsonSchemaContext->values) ? $this->buildValues($property->jsonSchemaContext->values) : null,
                'push' => $this->phpize($property, 'push', 'bool'),
                'security' => $this->phpize($property, 'security', 'string'),
                'securityPostDenormalize' => $this->phpize($property, 'securityPostDenormalize', 'string'),
                'types' => $this->buildArrayValue($property, 'type'),
                'builtinTypes' => $this->buildArrayValue($property, 'builtinType'),
                'schema' => isset($property->schema->values) ? $this->buildValues($property->schema->values) : null,
                'initializable' => $this->phpize($property, 'initializable', 'bool'),
                'extraProperties' => $this->buildExtraProperties($property, 'extraProperties'),
                'iris' => $this->buildArrayValue($property, 'iri'),
                'genId' => $this->phpize($property, 'genId', 'bool'),
            ];
        }
    }

    private function buildExtraProperties(\SimpleXMLElement $resource, string $key = null): ?array
    {
        if (null !== $key) {
            if (!isset($resource->{$key})) {
                return null;
            }

            $resource = $resource->{$key};
        }

        return $this->buildValues($resource->values);
    }

    /**
     * @return string[]
     */
    private function buildValues(\SimpleXMLElement $resource): array
    {
        $data = [];
        foreach ($resource->value as $value) {
            if (null !== $value->attributes()->name) {
                $data[(string) $value->attributes()->name] = isset($value->values) ? $this->buildValues($value->values) : (string) $value;
                continue;
            }

            $data[] = isset($value->values) ? $this->buildValues($value->values) : (string) $value;
        }

        return $data;
    }

    private function buildArrayValue(?\SimpleXMLElement $resource, string $key, $default = null)
    {
        if (!isset($resource->{$key.'s'}->{$key})) {
            return $default;
        }

        return (array) $resource->{$key.'s'}->{$key};
    }

    /**
     * Transforms an XML attribute's value in a PHP value.
     *
     * @param mixed|null $default
     *
     * @return string|int|bool|array|null
     */
    private function phpize(\SimpleXMLElement $resource, string $key, string $type, $default = null)
    {
        if (!isset($resource[$key])) {
            return $default;
        }

        switch ($type) {
            case 'bool|string':
                return \in_array((string) $resource[$key], ['1', '0', 'true', 'false'], true) ? $this->phpize($resource, $key, 'bool') : $this->phpize($resource, $key, 'string');
            case 'string':
                return (string) $resource[$key];
            case 'integer':
                return (int) $resource[$key];
            case 'bool':
                return (bool) XmlUtils::phpize($resource[$key]);
        }

        return null;
    }
}
