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

namespace ApiPlatform\Core\Metadata\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 *
 * @deprecated
 */
trait ApiResourceToLegacyResourceMetadataTrait
{
    private $camelCaseToSnakeCaseNameConverter;

    private function transformResourceToResourceMetadata(ApiResource $resource): ResourceMetadata
    {
        $collectionOperations = [];
        $itemOperations = [];
        foreach ($resource->getOperations() as $name => $operation) {
            $arrayOperation = $this->toArray($operation);

            if (!isset($arrayOperation['openapi_context'])) {
                $arrayOperation['openapi_context'] = [];
            }

            $arrayOperation['openapi_context']['operationId'] = $name;
            $arrayOperation['composite_identifier'] = $this->hasCompositeIdentifier($operation);

            if (HttpOperation::METHOD_POST === $operation->getMethod() && !$operation->getUriVariables()) {
                $collectionOperations[$name] = $arrayOperation;
                continue;
            }

            if ($operation instanceof CollectionOperationInterface) {
                $collectionOperations[$name] = $arrayOperation;
                continue;
            }

            $itemOperations[$name] = $arrayOperation;
        }

        $attributes = $this->toArray($resource);

        $graphqlOperations = $resource->getGraphQlOperations() ? [] : null;
        foreach ($resource->getGraphQlOperations() ?? [] as $operationName => $operation) {
            $graphqlOperations[$operationName] = $this->toArray($operation);
        }

        return new ResourceMetadata($resource->getShortName(), $resource->getDescription(), $resource->getTypes()[0] ?? null, $itemOperations, $collectionOperations, $attributes, null, $graphqlOperations);
    }

    private function toArray($object): array
    {
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        $arr = [];
        foreach (get_class_methods($object) as $methodName) {
            if ('getOperations' === $methodName || 0 !== strpos($methodName, 'get')) {
                continue;
            }

            if (null === $value = $object->{$methodName}()) {
                continue;
            }

            $arr[$this->camelCaseToSnakeCaseNameConverter->normalize(lcfirst(substr($methodName, 3)))] = $value;
        }

        return $this->transformUriVariablesToIdentifiers($arr);
    }

    private function transformUriVariablesToIdentifiers(array $arrayOperation): array
    {
        if (!isset($arrayOperation['uri_variables'])) {
            return $arrayOperation;
        }

        if (!\is_array($arrayOperation['uri_variables'])) {
            $arrayOperation['identifiers'] = $arrayOperation['uri_variables'];

            return $arrayOperation;
        }

        $arrayOperation['identifiers'] = [];
        foreach ($arrayOperation['uri_variables'] as $parameterName => $identifiedBy) {
            if ($identifiedBy->getExpandedValue() ?? false) {
                continue;
            }

            if (1 === \count($identifiedBy->getIdentifiers() ?? ['id'])) {
                $arrayOperation['identifiers'][$parameterName] = [$identifiedBy->getFromClass(), $identifiedBy->getIdentifiers()[0] ?? ['id']];
                continue;
            }

            foreach ($identifiedBy->getIdentifiers() as $identifier) {
                $arrayOperation['identifiers'][$identifier] = [$identifiedBy->getFromClass(), $identifier];
            }
        }

        return $arrayOperation;
    }

    private function hasCompositeIdentifier(HttpOperation $operation): bool
    {
        foreach ($operation->getUriVariables() ?? [] as $parameterName => $uriVariable) {
            if ($uriVariable->getCompositeIdentifier()) {
                return true;
            }
        }

        return false;
    }
}
