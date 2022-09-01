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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Extracts an array of metadata from a list of XML files.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class XmlResourceExtractor extends AbstractResourceExtractor
{
    use ResourceExtractorTrait;

    public const SCHEMA = __DIR__.'/schema/resources.xsd';

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
                simplexml_import_dom(XmlUtils::loadFile($path, XmlPropertyExtractor::SCHEMA));
            } catch (\InvalidArgumentException $error) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            // It's a property: ignore error
            return;
        }

        foreach ($xml->resource as $resource) {
            $base = $this->buildExtendedBase($resource);
            $this->resources[$this->resolve((string) $resource['class'])][] = array_merge($base, [
                'class' => $this->phpize($resource, 'class', 'string'),
                'operations' => $this->buildOperations($resource, $base),
                'graphQlOperations' => $this->buildGraphQlOperations($resource, $base),
            ]);
        }
    }

    private function buildExtendedBase(\SimpleXMLElement $resource): array
    {
        return array_merge($this->buildBase($resource), [
            'uriTemplate' => $this->phpize($resource, 'uriTemplate', 'string'),
            'routePrefix' => $this->phpize($resource, 'routePrefix', 'string'),
            'stateless' => $this->phpize($resource, 'stateless', 'bool'),
            'sunset' => $this->phpize($resource, 'sunset', 'string'),
            'acceptPatch' => $this->phpize($resource, 'acceptPatch', 'string'),
            'status' => $this->phpize($resource, 'status', 'integer'),
            'host' => $this->phpize($resource, 'host', 'string'),
            'condition' => $this->phpize($resource, 'condition', 'string'),
            'controller' => $this->phpize($resource, 'controller', 'string'),
            'types' => $this->buildArrayValue($resource, 'type'),
            'formats' => $this->buildFormats($resource, 'formats'),
            'inputFormats' => $this->buildFormats($resource, 'inputFormats'),
            'outputFormats' => $this->buildFormats($resource, 'outputFormats'),
            'uriVariables' => $this->buildUriVariables($resource),
            'defaults' => isset($resource->defaults->values) ? $this->buildValues($resource->defaults->values) : null,
            'requirements' => $this->buildRequirements($resource),
            'options' => isset($resource->options->values) ? $this->buildValues($resource->options->values) : null,
            'schemes' => $this->buildArrayValue($resource, 'scheme'),
            'cacheHeaders' => $this->buildCacheHeaders($resource),
            'hydraContext' => isset($resource->hydraContext->values) ? $this->buildValues($resource->hydraContext->values) : null,
            'openapiContext' => isset($resource->openapiContext->values) ? $this->buildValues($resource->openapiContext->values) : null,
            'paginationViaCursor' => $this->buildPaginationViaCursor($resource),
            'exceptionToStatus' => $this->buildExceptionToStatus($resource),
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
        ]);
    }

    private function buildBase(\SimpleXMLElement $resource): array
    {
        return [
            'shortName' => $this->phpize($resource, 'shortName', 'string'),
            'description' => $this->phpize($resource, 'description', 'string'),
            'urlGenerationStrategy' => $this->phpize($resource, 'urlGenerationStrategy', 'integer'),
            'deprecationReason' => $this->phpize($resource, 'deprecationReason', 'string'),
            'elasticsearch' => $this->phpize($resource, 'elasticsearch', 'bool'),
            'messenger' => $this->phpize($resource, 'messenger', 'bool|string'),
            'mercure' => $this->buildMercure($resource),
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
            'processor' => $this->phpize($resource, 'processor', 'string'),
            'provider' => $this->phpize($resource, 'provider', 'string'),
            'security' => $this->phpize($resource, 'security', 'string'),
            'securityMessage' => $this->phpize($resource, 'securityMessage', 'string'),
            'securityPostDenormalize' => $this->phpize($resource, 'securityPostDenormalize', 'string'),
            'securityPostDenormalizeMessage' => $this->phpize($resource, 'securityPostDenormalizeMessage', 'string'),
            'securityPostValidation' => $this->phpize($resource, 'securityPostValidation', 'string'),
            'securityPostValidationMessage' => $this->phpize($resource, 'securityPostValidationMessage', 'string'),
            'normalizationContext' => isset($resource->normalizationContext->values) ? $this->buildValues($resource->normalizationContext->values) : null,
            'denormalizationContext' => isset($resource->denormalizationContext->values) ? $this->buildValues($resource->denormalizationContext->values) : null,
            'validationContext' => isset($resource->validationContext->values) ? $this->buildValues($resource->validationContext->values) : null,
            'filters' => $this->buildArrayValue($resource, 'filter'),
            'order' => isset($resource->order->values) ? $this->buildValues($resource->order->values) : null,
            'extraProperties' => $this->buildExtraProperties($resource, 'extraProperties'),
            'read' => $this->phpize($resource, 'read', 'bool'),
            'write' => $this->phpize($resource, 'write', 'bool'),
        ];
    }

    private function buildFormats(\SimpleXMLElement $resource, string $key): ?array
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

    private function buildUriVariables(\SimpleXMLElement $resource): ?array
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

            if ($fromProperty = $this->phpize($data, 'fromProperty', 'string')) {
                $uriVariables[$parameterName]['from_property'] = $fromProperty;
            }
            if ($toProperty = $this->phpize($data, 'toProperty', 'string')) {
                $uriVariables[$parameterName]['to_property'] = $toProperty;
            }
            if ($fromClass = $this->phpize($data, 'fromClass', 'string')) {
                $uriVariables[$parameterName]['from_class'] = $fromClass;
            }
            if ($toClass = $this->phpize($data, 'toClass', 'string')) {
                $uriVariables[$parameterName]['to_class'] = $toClass;
            }
            if (isset($data->identifiers->values)) {
                $uriVariables[$parameterName]['identifiers'] = $this->buildValues($data->identifiers->values);
            }
            if (null !== ($compositeIdentifier = $this->phpize($data, 'compositeIdentifier', 'bool'))) {
                $uriVariables[$parameterName]['composite_identifier'] = $compositeIdentifier;
            }
        }

        return $uriVariables;
    }

    private function buildCacheHeaders(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->cacheHeaders->cacheHeader)) {
            return null;
        }

        $data = [];
        foreach ($resource->cacheHeaders->cacheHeader as $cacheHeader) {
            if (isset($cacheHeader->values->value)) {
                $data[(string) $cacheHeader['name']] = $this->buildValues($cacheHeader->values);
                continue;
            }

            $data[(string) $cacheHeader['name']] = (string) $cacheHeader;
        }

        return $data;
    }

    private function buildRequirements(\SimpleXMLElement $resource): ?array
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
    private function buildMercure(\SimpleXMLElement $resource)
    {
        if (!isset($resource->mercure)) {
            return null;
        }

        if (null !== $resource->mercure->attributes()->private) {
            return ['private' => $this->phpize($resource->mercure->attributes(), 'private', 'bool')];
        }

        return true;
    }

    private function buildPaginationViaCursor(\SimpleXMLElement $resource): ?array
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

    private function buildExceptionToStatus(\SimpleXMLElement $resource): ?array
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

    private function buildOperations(\SimpleXMLElement $resource, array $root): ?array
    {
        if (!isset($resource->operations->operation)) {
            return null;
        }

        $data = [];
        foreach ($resource->operations->operation as $operation) {
            $datum = $this->buildExtendedBase($operation);
            foreach ($datum as $key => $value) {
                if (null === $value) {
                    $datum[$key] = $root[$key];
                }
            }

            if (\in_array((string) $operation['class'], [GetCollection::class, Post::class], true)) {
                $datum['itemUriTemplate'] = $this->phpize($operation, 'itemUriTemplate', 'string');
            }

            $data[] = array_merge($datum, [
                'openapi' => $this->phpize($operation, 'openapi', 'bool'),
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

    private function buildGraphQlOperations(\SimpleXMLElement $resource, array $root): ?array
    {
        if (!isset($resource->graphQlOperations->mutation) && !isset($resource->graphQlOperations->query) && !isset($resource->graphQlOperations->subscription)) {
            return null;
        }

        $data = [];
        foreach (['mutation' => Mutation::class, 'query' => Query::class, 'subscription' => Subscription::class] as $type => $class) {
            foreach ($resource->graphQlOperations->{$type} as $operation) {
                $datum = $this->buildBase($operation);
                foreach ($datum as $key => $value) {
                    if (null === $value) {
                        $datum[$key] = $root[$key];
                    }
                }

                $data[] = array_merge($datum, [
                    'graphql_operation_class' => $class,
                    'collection' => $this->phpize($operation, 'collection', 'bool'),
                    'resolver' => $this->phpize($operation, 'resolver', 'string'),
                    'args' => $this->buildArgs($operation),
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
}
