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

namespace ApiPlatform\Tests\Metadata\Extractor\Adapter;

use ApiPlatform\Tests\Metadata\Extractor\ResourceMetadataCompatibilityTest;

/**
 * XML adapter for ResourceMetadataCompatibilityTest.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class XmlResourceAdapter implements ResourceAdapterInterface
{
    private const ATTRIBUTES = [
        'uriTemplate',
        'shortName',
        'description',
        'routePrefix',
        'stateless',
        'sunset',
        'class',
        'acceptPatch',
        'status',
        'host',
        'condition',
        'controller',
        'urlGenerationStrategy',
        'deprecationReason',
        'elasticsearch',
        'messenger',
        'input',
        'output',
        'fetchPartial',
        'forceEager',
        'paginationClientEnabled',
        'paginationClientItemsPerPage',
        'paginationClientPartial',
        'paginationEnabled',
        'paginationFetchJoinCollection',
        'paginationUseOutputWalkers',
        'paginationItemsPerPage',
        'paginationMaximumItemsPerPage',
        'paginationPartial',
        'paginationType',
        'processor',
        'provider',
        'security',
        'securityMessage',
        'securityPostDenormalize',
        'securityPostDenormalizeMessage',
        'securityPostValidation',
        'securityPostValidationMessage',
        'queryParameterValidationEnabled',
    ];

    public function __invoke(string $resourceClass, array $parameters, array $fixtures): array
    {
        $xml = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<resources xmlns="https://api-platform.com/schema/metadata/resources-3.0"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
           https://api-platform.com/schema/metadata/resources-3.0.xsd">
</resources>
XML
        );

        foreach ($fixtures as $fixture) {
            $resource = $xml->addChild('resource');

            if (null === $fixture) {
                $resource->addAttribute('class', $resourceClass);
                continue;
            }

            $fixture['class'] = $resourceClass;
            foreach ($parameters as $parameter) {
                $parameterName = $parameter->getName();
                $value = \array_key_exists($parameterName, $fixture) ? $fixture[$parameterName] : null;

                if ('compositeIdentifier' === $parameterName || 'provider' === $parameterName || 'processor' === $parameterName) {
                    continue;
                }

                if (method_exists($this, 'build'.ucfirst($parameterName))) {
                    $this->{'build'.ucfirst($parameterName)}($resource, $value);
                    continue;
                }

                if (\in_array($parameterName, self::ATTRIBUTES, true) && \is_scalar($value)) {
                    $resource->addAttribute($parameterName, $this->parse($value));
                    continue;
                }

                throw new \LogicException(sprintf('Cannot adapt attribute or child "%s". Please add fixtures in '.ResourceMetadataCompatibilityTest::class.' and create a "%s" method in %s.', $parameterName, 'build'.ucfirst($parameterName), __CLASS__));
            }
        }

        $filename = __DIR__.'/resources.xml';
        $xml->asXML($filename);

        return [$filename];
    }

    private function buildTypes(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('types');
        foreach ($values as $value) {
            $node->addChild('type', $value);
        }
    }

    private function buildInputFormats(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildFormats($resource, $values, 'inputFormats');
    }

    private function buildOutputFormats(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildFormats($resource, $values, 'outputFormats');
    }

    private function buildUriVariables(\SimpleXMLElement $resource, array $values): void
    {
        $child = $resource->addChild('uriVariables');
        foreach ($values as $parameterName => $value) {
            $grandChild = $child->addChild('uriVariable');
            $grandChild->addAttribute('parameterName', $parameterName);
            if (!isset($value['fromClass'])) {
                $value['fromClass'] = $value[0];
                $value['fromProperty'] = $value[1];
                unset($value[0], $value[1]);
            }
            foreach ($value as $key => $data) {
                $grandChild->addAttribute($key, $this->parse($data));
            }
        }
    }

    private function buildDefaults(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('defaults'), $values);
    }

    private function buildMercure(\SimpleXMLElement $resource, $values): void
    {
        $child = $resource->addChild('mercure');
        if (\is_array($values)) {
            foreach ($values as $key => $value) {
                if (\is_string($key)) {
                    $child->addAttribute($key, $this->parse($value));
                }
            }
        }
    }

    private function buildRequirements(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('requirements');
        foreach ($values as $key => $value) {
            $node->addChild('requirement', $value)->addAttribute('property', $key);
        }
    }

    private function buildOptions(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('options'), $values);
    }

    private function buildSchemes(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('schemes');
        foreach ($values as $value) {
            $node->addChild('scheme', $value);
        }
    }

    private function buildCacheHeaders(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('cacheHeaders');
        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $child = $node->addChild('cacheHeader');
                $this->buildValues($child, $value);
            } else {
                $child = $node->addChild('cacheHeader', $this->parse($value));
            }
            $child->addAttribute('name', $key);
        }
    }

    private function buildNormalizationContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('normalizationContext'), $values);
    }

    private function buildDenormalizationContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('denormalizationContext'), $values);
    }

    private function buildHydraContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('hydraContext'), $values);
    }

    private function buildOpenapiContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('openapiContext'), $values);
    }

    private function buildValidationContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('validationContext'), $values);
    }

    private function buildFilters(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('filters');
        foreach ($values as $value) {
            $node->addChild('filter', $value);
        }
    }

    private function buildOrder(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('order'), $values);
    }

    private function buildPaginationViaCursor(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('paginationViaCursor');
        foreach ($values as $key => $value) {
            $child = $node->addChild('paginationField');
            $child->addAttribute('field', $key);
            $child->addAttribute('direction', $value);
        }
    }

    private function buildExceptionToStatus(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('exceptionToStatus');
        foreach ($values as $key => $value) {
            $child = $node->addChild('exception');
            $child->addAttribute('class', $key);
            $child->addAttribute('statusCode', $this->parse($value));
        }
    }

    private function buildExtraProperties(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('extraProperties'), $values);
    }

    private function buildArgs(\SimpleXMLElement $resource, array $args): void
    {
        $child = $resource->addChild('args');
        foreach ($args as $id => $values) {
            $grandChild = $child->addChild('arg');
            $grandChild->addAttribute('id', $id);
            $this->buildValues($grandChild, $values);
        }
    }

    private function buildGraphQlOperations(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('graphQlOperations');
        foreach ($values as $type => $operations) {
            switch ($type) {
                case 'queries':
                    $operationType = 'query';
                    break;
                default:
                    $operationType = substr($type, 0, -1);
                    break;
            }

            foreach ($operations as $key => $value) {
                $child = $node->addChild($operationType);
                if (\is_string($key)) {
                    $child->addAttribute('name', $key);
                }
                foreach ($value as $index => $data) {
                    if (method_exists($this, 'build'.ucfirst($index))) {
                        $this->{'build'.ucfirst($index)}($child, $data);
                        continue;
                    }

                    if (\is_string($data) || null === $data || is_numeric($data) || \is_bool($data)) {
                        $child->addAttribute($index, $this->parse($data));
                        continue;
                    }

                    throw new \LogicException(sprintf('Cannot adapt graphQlOperation attribute or child "%s". Please create a "%s" method in %s.', $index, 'build'.ucfirst($index), __CLASS__));
                }
            }
        }
    }

    private function buildOperations(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('operations');
        foreach ($values as $value) {
            $child = $node->addChild('operation');
            foreach ($value as $index => $data) {
                if (method_exists($this, 'build'.ucfirst($index))) {
                    $this->{'build'.ucfirst($index)}($child, $data);
                    continue;
                }

                if (\is_string($data) || null === $data || is_numeric($data) || \is_bool($data)) {
                    $child->addAttribute($index, $this->parse($data));
                    continue;
                }

                throw new \LogicException(sprintf('Cannot adapt operation attribute or child "%s". Please create a "%s" method in %s.', $index, 'build'.ucfirst($index), __CLASS__));
            }
        }
    }

    private function buildFormats(\SimpleXMLElement $resource, array $values, string $name = 'formats'): void
    {
        $node = $resource->addChild($name);
        foreach ($values as $key => $value) {
            $node->addChild('format', $value)->addAttribute('name', $key);
        }
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

    private function parse($value): ?string
    {
        if (null === $value) {
            return null;
        }

        return \is_bool($value) ? (true === $value ? 'true' : 'false') : (string) $value;
    }
}
