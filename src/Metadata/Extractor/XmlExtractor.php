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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
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
            $base = $this->getExtendedBase($resource);
            $this->resources[$this->resolve((string) $resource['class'])][] = array_merge($base, [
                'class' => $this->phpize($resource, 'class', 'string'),
                'properties' => $this->getProperties($resource),
                'operations' => $this->getOperations($resource, $base),
                'graphQlOperations' => $this->getGraphQlOperations($resource, $base),
            ]);
        }
    }

    private function getExtendedBase(\SimpleXMLElement $resource): array
    {
        return array_merge($this->getBase($resource), [
            'uriTemplate' => $this->phpize($resource, 'uriTemplate', 'string'),
            'routePrefix' => $this->phpize($resource, 'routePrefix', 'string'),
            'stateless' => $this->phpize($resource, 'stateless', 'bool'),
            'sunset' => $this->phpize($resource, 'sunset', 'string'),
            'acceptPatch' => $this->phpize($resource, 'acceptPatch', 'string'),
            'status' => $this->phpize($resource, 'status', 'integer'),
            'host' => $this->phpize($resource, 'host', 'string'),
            'condition' => $this->phpize($resource, 'condition', 'string'),
            'controller' => $this->phpize($resource, 'controller', 'string'),
            'types' => $this->getArrayValue($resource, 'type'),
            'formats' => $this->getFormats($resource, 'formats'),
            'inputFormats' => $this->getFormats($resource, 'inputFormats'),
            'outputFormats' => $this->getFormats($resource, 'outputFormats'),
            'uriVariables' => $this->getUriVariables($resource),
            'defaults' => isset($resource->defaults->values) ? $this->getValues($resource->defaults->values) : null,
            'requirements' => $this->getRequirements($resource),
            'options' => isset($resource->options->values) ? $this->getValues($resource->options->values) : null,
            'schemes' => $this->getArrayValue($resource, 'scheme'),
            'cacheHeaders' => $this->getCacheHeaders($resource),
            'hydraContext' => isset($resource->hydraContext->values) ? $this->getValues($resource->hydraContext->values) : null,
            'openapiContext' => isset($resource->openapiContext->values) ? $this->getValues($resource->openapiContext->values) : null,
            'paginationViaCursor' => $this->getPaginationViaCursor($resource),
            'compositeIdentifier' => $this->phpize($resource, 'compositeIdentifier', 'bool'),
            'exceptionToStatus' => $this->getExceptionToStatus($resource),
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
        ]);
    }

    private function getBase(\SimpleXMLElement $resource): array
    {
        return [
            'shortName' => $this->phpize($resource, 'shortName', 'string'),
            'description' => $this->phpize($resource, 'description', 'string'),
            'urlGenerationStrategy' => $this->phpize($resource, 'urlGenerationStrategy', 'integer'),
            'deprecationReason' => $this->phpize($resource, 'deprecationReason', 'string'),
            'elasticsearch' => $this->phpize($resource, 'elasticsearch', 'bool'),
            'messenger' => $this->phpize($resource, 'messenger', 'bool|string'),
            'mercure' => $this->getMercure($resource),
            'input' => $this->phpize($resource, 'input', 'bool|string'),
            'output' => $this->phpize($resource, 'output', 'bool|string'),
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
            'securityPostValidation' => $this->phpize($resource, 'securityPostValidation', 'string'),
            'securityPostValidationMessage' => $this->phpize($resource, 'securityPostValidationMessage', 'string'),
            'normalizationContext' => isset($resource->normalizationContext->values) ? $this->getValues($resource->normalizationContext->values) : null,
            'denormalizationContext' => isset($resource->denormalizationContext->values) ? $this->getValues($resource->denormalizationContext->values) : null,
            'validationContext' => isset($resource->validationContext->values) ? $this->getValues($resource->validationContext->values) : null,
            'filters' => $this->getArrayValue($resource, 'filter'),
            'order' => isset($resource->order->values) ? $this->getValues($resource->order->values) : null,
            'extraProperties' => $this->getExtraProperties($resource, 'extraProperties'),
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

    private function getUriVariables(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->uriVariables->uriVariable)) {
            return null;
        }

        $uriVariables = [];
        foreach ($resource->uriVariables->uriVariable as $data) {
            $parameterName = (string) $data['parameterName'];
            if (1 === \count($data->attributes())) {
                $uriVariables[$parameterName] = $parameterName;
                continue;
            }

            if (null !== ($class = $this->phpize($data, 'class', 'string'))) {
                $uriVariables[$parameterName]['class'] = $class;
            }
            if (null !== ($property = $this->phpize($data, 'property', 'string'))) {
                $uriVariables[$parameterName]['property'] = $property;
            }
            if (null !== ($inverseProperty = $this->phpize($data, 'inverseProperty', 'string'))) {
                $uriVariables[$parameterName]['inverse_property'] = $inverseProperty;
            }
            if (isset($data->identifiers->values)) {
                $uriVariables[$parameterName]['identifiers'] = $this->getValues($data->identifiers->values);
            }
            if (null !== ($compositeIdentifier = $this->phpize($data, 'compositeIdentifier', 'bool'))) {
                $uriVariables[$parameterName]['composite_identifier'] = $compositeIdentifier;
            }
        }

        return $uriVariables;
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

    private function getRequirements(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->requirements->requirement)) {
            return null;
        }

        $data = [];
        foreach ($resource->requirements->requirement as $requirement) {
            $data[(string) $requirement->attributes()->property] = (string) $requirement;
        }

        return $data;
    }

    /**
     * @return bool|string[]|null
     */
    private function getMercure(\SimpleXMLElement $resource)
    {
        if (!isset($resource->mercure)) {
            return null;
        }

        if (null !== $resource->mercure->attributes()->private) {
            return ['private' => $this->phpize($resource->mercure->attributes(), 'private', 'bool')];
        }

        return true;
    }

    private function getPaginationViaCursor(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->paginationViaCursor->paginationField)) {
            return null;
        }

        $data = [];
        foreach ($resource->paginationViaCursor->paginationField as $paginationField) {
            $data[(string) $paginationField['field']] = (string) $paginationField['direction'];
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

        return $this->getValues($resource->values);
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
                'default' => $this->phpize($property, 'default', 'string'),
                'example' => $this->phpize($property, 'example', 'string'),
                'deprecationReason' => $this->phpize($property, 'deprecationReason', 'string'),
                'fetchable' => $this->phpize($property, 'fetchable', 'bool'),
                'fetchEager' => $this->phpize($property, 'fetchEager', 'bool'),
                'jsonldContext' => isset($property->jsonldContext->values) ? $this->getValues($property->jsonldContext->values) : null,
                'openapiContext' => isset($property->openapiContext->values) ? $this->getValues($property->openapiContext->values) : null,
                'push' => $this->phpize($property, 'push', 'bool'),
                'security' => $this->phpize($property, 'security', 'string'),
                'securityPostDenormalize' => $this->phpize($property, 'securityPostDenormalize', 'string'),
                'types' => $this->getArrayValue($property, 'type'),
                'builtinTypes' => isset($property->builtinTypes->values) ? $this->getValues($property->builtinTypes->values) : null,
                'schema' => isset($property->schema->values) ? $this->getValues($property->schema->values) : null,
                'initializable' => $this->phpize($property, 'initializable', 'bool'),
                'extraProperties' => $this->getExtraProperties($property, 'extraProperties'),
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
            $datum = $this->getExtendedBase($operation);
            foreach ($datum as $key => $value) {
                if (null === $value) {
                    $datum[$key] = $root[$key];
                }
            }

            $data[] = array_merge($datum, [
                'collection' => $this->phpize($operation, 'collection', 'bool'),
                'class' => (string) $operation['class'],
                'method' => $this->phpize($operation, 'method', 'string'),
                'read' => $this->phpize($operation, 'read', 'bool'),
                'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                'validate' => $this->phpize($operation, 'validate', 'bool'),
                'write' => $this->phpize($operation, 'write', 'bool'),
                'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                'queryParameterValidate' => $this->phpize($operation, 'queryParameterValidate', 'bool'),
                'priority' => $this->phpize($operation, 'priority', 'integer'),
                'name' => $this->phpize($operation, 'name', 'string'),
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
        foreach (['mutation' => Mutation::class, 'query' => Query::class, 'subscription' => Subscription::class] as $type => $class) {
            foreach ($resource->graphQlOperations->{$type} as $operation) {
                $datum = $this->getBase($operation);
                foreach ($datum as $key => $value) {
                    if (null === $value) {
                        $datum[$key] = $root[$key];
                    }
                }

                $data[] = array_merge($datum, [
                    'graphql_operation_class' => $class,
                    'collection' => $this->phpize($operation, 'collection', 'bool'),
                    'resolver' => $this->phpize($operation, 'resolver', 'string'),
                    'args' => $this->getArgs($operation),
                    'class' => $this->phpize($operation, 'class', 'string'),
                    'read' => $this->phpize($operation, 'read', 'bool'),
                    'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                    'validate' => $this->phpize($operation, 'validate', 'bool'),
                    'write' => $this->phpize($operation, 'write', 'bool'),
                    'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                    'priority' => $this->phpize($operation, 'priority', 'integer'),
                ]);
            }
        }

        return $data;
    }

    private function getArgs(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->args->arg)) {
            return null;
        }

        $data = [];
        foreach ($resource->args->arg as $arg) {
            $data[(string) $arg['id']] = $this->getValues($arg->values);
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
        $data = [];
        foreach ($resource->value as $value) {
            if (null !== $value->attributes()->name) {
                $data[(string) $value->attributes()->name] = isset($value->values) ? $this->getValues($value->values) : (string) $value;
                continue;
            }

            $data[] = isset($value->values) ? $this->getValues($value->values) : (string) $value;
        }

        return $data;
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
