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
        'stateOptions',
        'collectDenormalizationErrors',
    ];

    /**
     * {@inheritdoc}
     */
    public function __invoke(string $resourceClass, array $parameters, array $fixtures): array
    {
        $xml = new \SimpleXMLElement(<<<XML_WRAP
<?xml version="1.0" encoding="UTF-8" ?>
<resources xmlns="https://api-platform.com/schema/metadata/resources-3.0"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
           https://api-platform.com/schema/metadata/resources-3.0.xsd">
</resources>
XML_WRAP
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

                throw new \LogicException(sprintf('Cannot adapt attribute or child "%s". Please add fixtures in '.ResourceMetadataCompatibilityTest::class.' and create a "%s" method in %s.', $parameterName, 'build'.ucfirst($parameterName), self::class));
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

    /**
     * TODO Remove in 4.0.
     *
     * @deprecated
     */
    private function buildOpenapiContext(\SimpleXMLElement $resource, array $values): void
    {
        $this->buildValues($resource->addChild('openapiContext'), $values);
    }

    private function buildOpenapi(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->openapi ?? $resource->addChild('openapi');

        if (isset($values['tags'])) {
            $tagsNode = $node->tags ?? $node->addChild('tags');
            foreach ($values['tags'] as $tag) {
                $tagsNode->addChild('tag', $tag);
            }
        }

        if (isset($values['responses'])) {
            $responsesNode = $node->responses ?? $node->addChild('responses');
            foreach ($values['responses'] as $status => $response) {
                $responseNode = $responsesNode->addChild('response');
                $responseNode->addAttribute('status', $status);
                if (isset($response['description'])) {
                    $responseNode->addAttribute('description', $response['description']);
                }
                if (isset($response['content'])) {
                    $this->buildValues($responseNode->addChild('content'), $response['content']);
                }
                if (isset($response['headers'])) {
                    $this->buildValues($responseNode->addChild('headers'), $response['headers']);
                }
                if (isset($response['links'])) {
                    $this->buildValues($responseNode->addChild('links'), $response['links']);
                }
            }
        }

        if (isset($values['externalDocs'])) {
            $externalDocsNode = $node->externalDocs ?? $node->addChild('externalDocs');
            if (isset($values['externalDocs']['description'])) {
                $externalDocsNode->addAttribute('description', $values['externalDocs']['description']);
            }
            if (isset($values['url']['description'])) {
                $externalDocsNode->addAttribute('url', $values['externalDocs']['url']);
            }
        }

        if (isset($values['parameters'])) {
            $parametersNode = $node->parameters ?? $node->addChild('parameters');
            foreach ($values['parameters'] as $name => $parameter) {
                $parameterNode = $parametersNode->addChild('parameter');
                $parameterNode->addAttribute('name', $name);
                if (isset($parameter['in'])) {
                    $parameterNode->addAttribute('in', $parameter['in']);
                }
                if (isset($parameter['description'])) {
                    $parameterNode->addAttribute('description', $parameter['description']);
                }
                if (isset($parameter['required'])) {
                    $parameterNode->addAttribute('required', $parameter['required']);
                }
                if (isset($parameter['deprecated'])) {
                    $parameterNode->addAttribute('deprecated', $parameter['deprecated']);
                }
                if (isset($parameter['allowEmptyValue'])) {
                    $parameterNode->addAttribute('allowEmptyValue', $parameter['allowEmptyValue']);
                }
                if (isset($parameter['style'])) {
                    $parameterNode->addAttribute('style', $parameter['style']);
                }
                if (isset($parameter['explode'])) {
                    $parameterNode->addAttribute('explode', $parameter['explode']);
                }
                if (isset($parameter['allowReserved'])) {
                    $parameterNode->addAttribute('allowReserved', $parameter['allowReserved']);
                }
                if (isset($parameter['example'])) {
                    $parameterNode->addAttribute('example', $parameter['example']);
                }
                if (isset($parameter['schema'])) {
                    $this->buildValues($parameterNode->addChild('schema'), $parameter['schema']);
                }
                if (isset($parameter['examples'])) {
                    $this->buildValues($parameterNode->addChild('examples'), $parameter['examples']);
                }
                if (isset($parameter['content'])) {
                    $this->buildValues($parameterNode->addChild('content'), $parameter['content']);
                }
            }
        }

        if (isset($values['requestBody'])) {
            $requestBodyNode = $node->requestBody ?? $node->addChild('requestBody');
            if (isset($values['requestBody']['content'])) {
                $this->buildValues($requestBodyNode->addChild('content'), $values['requestBody']['content']);
            }
            if (isset($values['requestBody']['description'])) {
                $requestBodyNode->addAttribute('description', $values['requestBody']['description']);
            }
            if (isset($values['requestBody']['required'])) {
                $requestBodyNode->addAttribute('required', $values['requestBody']['required']);
            }
        }

        if (isset($values['callbacks'])) {
            $this->buildValues($node->callbacks ?? $node->addChild('callbacks'), $values['callbacks']);
        }

        if (isset($values['security'])) {
            $this->buildValues($node->security ?? $node->addChild('security'), $values['security']);
        }

        if (isset($values['servers'])) {
            $serversNode = $node->servers ?? $node->addChild('servers');
            foreach ($values['servers'] as $server) {
                $serverNode = $serversNode->addChild('serverNode');
                if (isset($server['url'])) {
                    $serverNode->addAttribute('url', $server['url']);
                }
                if (isset($server['description'])) {
                    $serverNode->addAttribute('description', $server['description']);
                }
                if (isset($server['variables'])) {
                    $this->buildValues($serverNode->addChild('variables'), $server['variables']);
                }
            }
        }

        if (isset($values['extensionProperties'])) {
            $this->buildValues($node->extensionProperties ?? $node->addChild('extensionProperties'), $values['extensionProperties']);
        }
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

    private function buildTranslation(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('translation');
        foreach ($values as $key => $value) {
            $node->addAttribute($key, $this->parse($value));
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

    private function buildExtraArgs(\SimpleXMLElement $resource, array $args): void
    {
        $child = $resource->addChild('extraArgs');
        foreach ($args as $id => $values) {
            $grandChild = $child->addChild('arg');
            $grandChild->addAttribute('id', $id);
            $this->buildValues($grandChild, $values);
        }
    }

    private function buildGraphQlOperations(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('graphQlOperations');
        foreach ($values as $value) {
            $child = $node->addChild('graphQlOperation');
            foreach ($value as $index => $data) {
                if (method_exists($this, 'build'.ucfirst($index))) {
                    $this->{'build'.ucfirst($index)}($child, $data);
                    continue;
                }

                if (\is_string($data) || null === $data || is_numeric($data) || \is_bool($data)) {
                    $child->addAttribute($index, $this->parse($data));
                    continue;
                }

                throw new \LogicException(sprintf('Cannot adapt graphQlOperation attribute or child "%s". Please create a "%s" method in %s.', $index, 'build'.ucfirst($index), self::class));
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

                throw new \LogicException(sprintf('Cannot adapt operation attribute or child "%s". Please create a "%s" method in %s.', $index, 'build'.ucfirst($index), self::class));
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

    private function buildStateOptions(\SimpleXMLElement $resource, array $values): void
    {
        $node = $resource->addChild('stateOptions');
        $childNode = $node->addChild(array_key_first($values));
        $childNode->addAttribute('index', $values[array_key_first($values)]['index']);
        $childNode->addAttribute('type', $values[array_key_first($values)]['type']);
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
