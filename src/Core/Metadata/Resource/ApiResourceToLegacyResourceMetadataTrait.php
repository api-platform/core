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

            if ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false) {
                $arrayOperation['composite_identifier'] = $operation->getCompositeIdentifier() ?? false;
            }

            if ($operation->isCollection()) {
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

        return $arr;
    }
}
