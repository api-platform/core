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

namespace ApiPlatform\Metadata\Tests\Extractor\Adapter;

use ApiPlatform\Metadata\Tests\Extractor\PropertyMetadataCompatibilityTest;

/**
 * XML adapter for PropertyMetadataCompatibilityTest.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class XmlPropertyAdapter implements PropertyAdapterInterface
{
    private const ATTRIBUTES = [
        'resource',
        'name',
        'description',
        'readable',
        'writable',
        'readableLink',
        'writableLink',
        'required',
        'identifier',
        'default',
        'example',
        'deprecationReason',
        'fetchable',
        'fetchEager',
        'push',
        'security',
        'securityPostDenormalize',
        'initializable',
        'iris',
        'genId',
    ];

    /**
     * {@inheritdoc}
     */
    public function __invoke(string $resourceClass, string $propertyName, array $parameters, array $fixtures): array
    {
        $xml = new \SimpleXMLElement(<<<XML_WRAP
<?xml version="1.0" encoding="UTF-8" ?>
<properties xmlns="https://api-platform.com/schema/metadata/properties-3.0"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="https://api-platform.com/schema/metadata/properties-3.0
           https://api-platform.com/schema/metadata/properties-3.0.xsd">
</properties>
XML_WRAP
        );

        $property = $xml->addChild('property');
        $property->addAttribute('name', $propertyName);
        $property->addAttribute('resource', $resourceClass);

        foreach ($parameters as $parameter) {
            $parameterName = $parameter->getName();
            $value = \array_key_exists($parameterName, $fixtures) ? $fixtures[$parameterName] : null;

            if (method_exists($this, 'build'.ucfirst($parameterName))) {
                $this->{'build'.ucfirst($parameterName)}($property, $value);
                continue;
            }

            if (\in_array($parameterName, self::ATTRIBUTES, true) && \is_scalar($value)) {
                $property->addAttribute($parameterName, $this->parse($value));
                continue;
            }

            throw new \LogicException(sprintf('Cannot adapt attribute or child "%s". Please add fixtures in '.PropertyMetadataCompatibilityTest::class.' and create a "%s" method in %s.', $parameterName, 'build'.ucfirst($parameterName), self::class));
        }

        $filename = __DIR__.'/properties.xml';
        $xml->asXML($filename);

        return [$filename];
    }

    private function buildBuiltinTypes(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('builtinTypes');
        foreach ($values as $value) {
            $node->addChild('builtinType', $value);
        }
    }

    private function buildSchema(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('schema'), $values);
    }

    private function buildTypes(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('types');
        foreach ($values as $value) {
            $node->addChild('type', $value);
        }
    }

    private function buildIris(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('iris');
        foreach ($values as $value) {
            $node->addChild('iri', $value);
        }
    }

    private function buildJsonldContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('jsonldContext'), $values);
    }

    private function buildOpenapiContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('openapiContext'), $values);
    }

    private function buildJsonSchemaContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('jsonSchemaContext'), $values);
    }

    private function buildExtraProperties(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('extraProperties'), $values);
    }

    private function buildValues(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('values');
        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $child = $node->addChild('value');
                $this->buildValues($child, $value);
            } else {
                $child = $node->addChild('value', $value);
            }
            if (\is_string($key)) {
                $child->addAttribute('name', $key);
            }
        }
    }

    private function parse(string|int|float|bool|null $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return \is_bool($value) ? (true === $value ? 'true' : 'false') : (string) $value;
    }
}
