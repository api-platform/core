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
final class XmlExtractor extends AbstractExtractor
{
    public const RESOURCE_SCHEMA = __DIR__.'/schema/metadata.xsd';

    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            /** @var \SimpleXMLElement $xml */
            $xml = simplexml_import_dom(XmlUtils::loadFile($path, self::RESOURCE_SCHEMA));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($xml->resource as $resource) {
            $resourceClass = $this->resolve((string) $resource['class']);
            $base = $this->getBase($resource);
            $this->resources[$resourceClass][] = array_merge($base, [
                'operations' => $this->getOperations($resource, $base),
                'graphQlOperations' => $this->getGraphQlOperations($resource, $base),
            ]);
        }
    }

    private function getBase(\SimpleXMLElement $resource): array
    {
        return [
            'uriTemplate' => $this->phpize($resource, 'uriTemplate', 'string'),
            'shortName' => $this->phpize($resource, 'shortName', 'string'),
            'description' => $this->phpize($resource, 'description', 'string'),
            'routePrefix' => $this->phpize($resource, 'routePrefix', 'string'),
            'stateless' => $this->phpize($resource, 'stateless', 'bool'),
            'sunset' => $this->phpize($resource, 'sunset', 'string'),
            'acceptPatch' => $this->phpize($resource, 'acceptPatch', 'string'),
            'host' => $this->phpize($resource, 'host', 'string'),
            'condition' => $this->phpize($resource, 'condition', 'string'),
            'controller' => $this->phpize($resource, 'controller', 'string'),
            'urlGenerationStrategy' => $this->phpize($resource, 'urlGenerationStrategy', 'integer'),
            'deprecationReason' => $this->phpize($resource, 'deprecationReason', 'string'),
            'elasticsearch' => $this->phpize($resource, 'elasticsearch', 'bool'),
            'fetchPartial' => $this->phpize($resource, 'fetchPartial', 'bool'),
            'forceEager' => $this->phpize($resource, 'forceEager', 'bool'),
            'paginationClientEnabled' => $this->phpize($resource, 'paginationClientEnabled', 'bool'),
            'paginationClientItemsPerPage' => $this->phpize($resource, 'paginationClientItemsPerPage', 'bool'),
            'paginationClientPartial' => $this->phpize($resource, 'paginationClientPartial', 'bool'),
            'paginationEnabled' => $this->phpize($resource, 'paginationEnabled', 'bool'),
            'paginationFetchJoinCollection' => $this->phpize($resource, 'paginationFetchJoinCollection', 'bool'),
            'paginationUseOutputWalkers' => $this->phpize($resource, 'paginationUseOutputWalkers', 'bool'),
            'paginationItemsPerPage' => $this->phpize($resource, 'paginationItemsPerPage', 'integer'),
            'paginationMaximumItemsPerPage' => $this->phpize($resource, 'paginationMaximumItemsPerPage', 'integer'),
            'paginationPartial' => $this->phpize($resource, 'paginationPartial', 'bool'),
            'paginationType' => $this->phpize($resource, 'paginationType', 'string'),
            'security' => $this->phpize($resource, 'security', 'string'),
            'securityMessage' => $this->phpize($resource, 'securityMessage', 'string'),
            'securityPostDenormalize' => $this->phpize($resource, 'securityPostDenormalize', 'string'),
            'securityPostDenormalizeMessage' => $this->phpize($resource, 'securityPostDenormalizeMessage', 'string'),
            'compositeIdentifiers' => $this->phpize($resource, 'compositeIdentifiers', 'bool'),
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
            'input' => $this->phpize($resource, 'input', 'bool|string'),
            'output' => $this->phpize($resource, 'output', 'bool|string'),
            'types' => $this->getArrayValue($resource, 'type'),
            'formats' => $this->getFormats($resource, 'formats'),
            'identifiers' => $this->getIdentifiers($resource),
            'inputFormats' => $this->getFormats($resource, 'inputFormats'),
            'outputFormats' => $this->getFormats($resource, 'outputFormats'),
            'defaults' => isset($resource->defaults->values) ? $this->getValues($resource->defaults->values) : null,
            'requirements' => isset($resource->requirements->values) ? $this->getValues($resource->requirements->values) : null,
            'options' => isset($resource->options->values) ? $this->getValues($resource->options->values) : null,
            'status' => $this->phpize($resource, 'status', 'integer'),
            'schemes' => $this->getAttributes($resource, 'schemes') ?: null,
            'cacheHeaders' => $this->getCacheHeaders($resource),
            'normalizationContext' => $this->getAttributes($resource, 'normalizationContext') ?: null,
            'denormalizationContext' => $this->getAttributes($resource, 'denormalizationContext') ?: null,
            'hydraContext' => $this->getAttributes($resource, 'hydraContext') ?: null,
            'openapiContext' => $this->getAttributes($resource, 'openapiContext') ?: null,
            'validationContext' => $this->getAttributes($resource, 'validationContext') ?: null,
            'filters' => $this->getArrayValue($resource, 'filter'),
            'mercure' => $this->getMercure($resource),
            'messenger' => $this->getMessenger($resource),
            'order' => $this->getOrder($resource),
            'paginationViaCursor' => $this->getPaginationViaCursor($resource),
            'exceptionToStatus' => $this->getExceptionToStatus($resource),
            'extraProperties' => $this->getExtraProperties($resource, 'extraProperties'),
            'properties' => $this->getProperties($resource),
        ];
    }

    private function getFormats(\SimpleXMLElement $resource, string $key): ?array
    {
        if (!isset($resource->{$key}->format)) {
            return null;
        }

        $data = [];
        foreach ($resource->{$key}->format as $format) {
            if (isset($format['name'])) {
                $data[(string) $format['name']] = (string) $format;
                continue;
            }

            $data[] = (string) $format;
        }

        return $data;
    }

    private function getIdentifiers(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->identifiers->identifier)) {
            return null;
        }

        $data = [];
        foreach ($resource->identifiers->identifier as $identifier) {
            $data[(string) $identifier['key']] = [
                $this->phpize($identifier, 'propertyName', 'string') ?: (string) $identifier['key'],
                $this->phpize($identifier, 'class', 'string') ?: $resource['class'],
            ];
        }

        return $data;
    }

    private function getCacheHeaders(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->cacheHeaders->cacheHeader)) {
            return null;
        }

        $data = [];
        foreach ($resource->cacheHeaders->cacheHeader as $cacheHeader) {
            if (isset($cacheHeader->values->value)) {
                $data[(string) $cacheHeader['name']] = $this->getValues($cacheHeader->values);
                continue;
            }

            $data[(string) $cacheHeader['name']] = (string) $cacheHeader;
        }

        return $data;
    }

    /**
     * @return bool|string|string[]|null
     */
    private function getMercure(\SimpleXMLElement $resource)
    {
        if (null !== $resource['mercure']) {
            return $this->phpize($resource, 'mercure', 'bool');
        }

        if (!isset($resource->mercure)) {
            return null;
        }

        if (isset($resource->mercure->attribute)) {
            return $this->getAttributes($resource->mercure);
        }

        return trim((string) $resource->mercure) ?: null;
    }

    /**
     * @return bool|string|null
     */
    private function getMessenger(\SimpleXMLElement $resource)
    {
        if (null !== $resource['messenger']) {
            return $this->phpize($resource, 'messenger', 'bool');
        }

        if (!isset($resource->messenger)) {
            return null;
        }

        return trim((string) $resource->messenger) ?: null;
    }

    /**
     * @return string[]|string|null
     */
    private function getOrder(\SimpleXMLElement $resource)
    {
        if (isset($resource->order->attribute)) {
            return $this->getAttributes($resource->order);
        }

        if (isset($resource->order->values->value)) {
            return $this->getValues($resource->order->values);
        }

        return null;
    }

    private function getPaginationViaCursor(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->paginationViaCursor->paginationField)) {
            return null;
        }

        $data = [];
        foreach ($resource->paginationViaCursor->paginationField as $paginationField) {
            $data[] = [
                'field' => (string) $paginationField['field'],
                'direction' => (string) $paginationField['direction'],
            ];
        }

        return $data;
    }

    private function getExceptionToStatus(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->exceptionToStatus->exception)) {
            return null;
        }

        $data = [];
        foreach ($resource->exceptionToStatus->exception as $exception) {
            $data[(string) $exception['class']] = (int) $exception['statusCode'];
        }

        return $data;
    }

    private function getExtraProperties(\SimpleXMLElement $resource, string $key = null): ?array
    {
        if (null !== $key) {
            if (!isset($resource->{$key})) {
                return null;
            }

            $resource = $resource->{$key};
        }

        $data = [];
        foreach ($resource->extraProperty as $extraProperty) {
            if (isset($extraProperty->extraProperty)) {
                $data[(string) $extraProperty['name']] = $this->getExtraProperties($extraProperty);
                continue;
            }

            $data[(string) $extraProperty['name']] = (string) $extraProperty;
        }

        return $data;
    }

    private function getProperties(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->properties->property)) {
            return null;
        }

        $data = [];
        foreach ($resource->properties->property as $property) {
            $data[(string) $property['name']] = [
                'description' => $this->phpize($property, 'description', 'string'),
                'readable' => $this->phpize($property, 'readable', 'bool'),
                'writable' => $this->phpize($property, 'writable', 'bool'),
                'readableLink' => $this->phpize($property, 'readableLink', 'bool'),
                'writableLink' => $this->phpize($property, 'writableLink', 'bool'),
                'required' => $this->phpize($property, 'required', 'bool'),
                'identifier' => $this->phpize($property, 'identifier', 'bool'),
                'deprecationReason' => $this->phpize($property, 'deprecationReason', 'string'),
                'fetchable' => $this->phpize($property, 'fetchable', 'bool'),
                'fetchEager' => $this->phpize($property, 'fetchEager', 'bool'),
                'push' => $this->phpize($property, 'push', 'bool'),
                'security' => $this->phpize($property, 'security', 'string'),
                'securityPostDenormalize' => $this->phpize($property, 'securityPostDenormalize', 'string'),
                'initializable' => $this->phpize($property, 'initializable', 'bool'),
                'jsonldContext' => $this->getAttributes($property, 'jsonldContext'),
                'openapiContext' => $this->getAttributes($property, 'openapiContext'),
                'types' => $this->getArrayValue($property, 'type'),
                'extraProperties' => $this->getExtraProperties($property, 'extraProperties'),
                'defaults' => isset($property->defaults->values) ? $this->getValues($property->defaults->values) : null,
                'example' => isset($property->example->values) ? $this->getValues($property->example->values) : null,
                'builtinTypes' => isset($property->builtinTypes->values) ? $this->getValues($property->builtinTypes->values) : null,
                'schema' => isset($property->schema->values) ? $this->getValues($property->schema->values) : null,
            ];
        }

        return $data;
    }

    private function getOperations(\SimpleXMLElement $resource, array $root): ?array
    {
        if (!isset($resource->operations->operation)) {
            return null;
        }

        $data = [];
        foreach ($resource->operations->operation as $operation) {
            $datum = $this->getBase($operation);
            foreach ($datum as $key => $value) {
                if (empty($value)) {
                    $datum[$key] = $root[$key];
                }
            }

            $data[] = array_merge($datum, [
                'read' => $this->phpize($operation, 'read', 'bool'),
                'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                'validate' => $this->phpize($operation, 'validate', 'bool'),
                'write' => $this->phpize($operation, 'write', 'bool'),
                'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                'queryParameterValidate' => $this->phpize($operation, 'queryParameterValidate', 'bool'),
                'priority' => $this->phpize($operation, 'priority', 'integer'),
                'name' => $this->phpize($operation, 'name', 'string'),
                'class' => (string) $operation['class'],
            ]);
        }

        return $data;
    }

    private function getGraphQlOperations(\SimpleXMLElement $resource, array $root): ?array
    {
        if (!isset($resource->graphQlOperations->mutation) && !isset($resource->graphQlOperations->query) && !isset($resource->graphQlOperations->subscription)) {
            return null;
        }

        $data = [];
        foreach (['mutation', 'query', 'subscription'] as $operationType) {
            foreach ($resource->graphQlOperations->{$operationType} as $operation) {
                $datum = $this->getBase($operation);
                foreach ($datum as $key => $value) {
                    if (empty($value)) {
                        $datum[$key] = $root[$key];
                    }
                }

                $data[] = array_merge($datum, [
                    'resolver' => $this->phpize($operation, 'resolver', 'string'),
                    'class' => $this->phpize($operation, 'class', 'string'),
                    'compositeIdentifier' => $this->phpize($operation, 'compositeIdentifier', 'bool'),
                    'paginationEnabled' => $this->phpize($operation, 'paginationEnabled', 'bool'),
                    'paginationType' => $this->phpize($operation, 'paginationType', 'string'),
                    'paginationItemsPerPage' => $this->phpize($operation, 'paginationItemsPerPage', 'integer'),
                    'paginationMaximumItemsPerPage' => $this->phpize($operation, 'paginationMaximumItemsPerPage', 'integer'),
                    'paginationPartial' => $this->phpize($operation, 'paginationPartial', 'bool'),
                    'paginationClientEnabled' => $this->phpize($operation, 'paginationClientEnabled', 'bool'),
                    'paginationClientItemsPerPage' => $this->phpize($operation, 'paginationClientItemsPerPage', 'bool'),
                    'paginationClientPartial' => $this->phpize($operation, 'paginationClientPartial', 'bool'),
                    'paginationFetchJoinCollection' => $this->phpize($operation, 'paginationFetchJoinCollection', 'bool'),
                    'paginationUseOutputWalkers' => $this->phpize($operation, 'paginationUseOutputWalkers', 'bool'),
                    'description' => $this->phpize($operation, 'description', 'string'),
                    'security' => $this->phpize($operation, 'security', 'string'),
                    'securityMessage' => $this->phpize($operation, 'securityMessage', 'string'),
                    'securityPostDenormalize' => $this->phpize($operation, 'securityPostDenormalize', 'string'),
                    'securityPostDenormalizeMessage' => $this->phpize($operation, 'securityPostDenormalizeMessage', 'string'),
                    'deprecationReason' => $this->phpize($operation, 'deprecationReason', 'string'),
                    'input' => $this->phpize($operation, 'input', 'bool|string'),
                    'output' => $this->phpize($operation, 'output', 'bool|string'),
                    'mercure' => $this->getMercure($operation),
                    'messenger' => $this->getMessenger($operation),
                    'elasticsearch' => $this->phpize($operation, 'elasticsearch', 'bool'),
                    'urlGenerationStrategy' => $this->phpize($operation, 'urlGenerationStrategy', 'integer'),
                    'read' => $this->phpize($operation, 'read', 'bool'),
                    'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                    'validate' => $this->phpize($operation, 'validate', 'bool'),
                    'write' => $this->phpize($operation, 'write', 'bool'),
                    'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                    'fetchPartial' => $this->phpize($operation, 'fetchPartial', 'bool'),
                    'forceEager' => $this->phpize($operation, 'forceEager', 'bool'),
                    'priority' => $this->phpize($operation, 'priority', 'integer'),
                ]);
            }
        }

        return $data;
    }

    /**
     * @return bool|string|string[]|null
     */
    private function getAttributes(\SimpleXMLElement $resource, string $key = null, bool $canBeDisabled = false)
    {
        if (null !== $key) {
            if ($canBeDisabled && null !== $resource[$key]) {
                return $this->phpize($resource, $key, 'bool');
            }

            if (!isset($resource->{$key})) {
                return null;
            }

            $resource = $resource->{$key};
        }

        $data = [];
        foreach ($resource->attribute as $attribute) {
            if (isset($attribute->attribute)) {
                $data[(string) $attribute['name']] = $this->getAttributes($attribute);
                continue;
            }

            if (isset($attribute->values->value)) {
                $data[(string) $attribute['name']] = $this->getValues($attribute->values);
                continue;
            }

            $data[(string) $attribute['name']] = (string) $attribute;
        }

        return $data;
    }

    /**
     * @return string[]
     */
    private function getValues(\SimpleXMLElement $resource): array
    {
        return array_map(function ($value) {
            return (string) $value;
        }, (array) $resource->value);
    }

    private function getArrayValue(?\SimpleXMLElement $resource, string $key, $default = null)
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
                return \in_array((string) $resource[$key], ['true', 'false'], true) ? $this->phpize($resource, $key, 'bool') : $this->phpize($resource, $key, 'string');
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
