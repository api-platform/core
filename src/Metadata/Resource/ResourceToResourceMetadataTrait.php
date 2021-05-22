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

use ApiPlatform\Metadata\Resource;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

trait ResourceToResourceMetadataTrait
{
    private $nameConverter;

    private function transformResourceToResourceMetadata(Resource $resource): ResourceMetadata
    {
        $collectionOperations = [];
        $itemOperations = [];
        foreach ($resource->operations as $name => $operation) {
            $operation = $this->toArray($operation);

            if (!\is_array($operation['openapi_context'])) {
                $operation['openapi_context'] = [];
            }

            $operation['openapi_context']['operationId'] = $name;

            if (!$operation['identifiers']) {
                $collectionOperations[$name] = $operation;
                continue;
            }

            $itemOperations[$name] = $operation;
        }
        $attributes = $this->toArray($resource);

        $graphql = isset($resource->graphQl) ? $this->toArray($resource->graphQl) : null;

        return new ResourceMetadata($resource->shortName, $resource->description, null, $itemOperations, $collectionOperations, $attributes, null, $graphql);
    }

    private function toArray($object): array
    {
        if (!$this->nameConverter) {
            $this->nameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        $arr = [];
        foreach ($object as $key => $value) {
            $arr[$this->nameConverter->normalize($key)] = $value;
        }

        return $arr;
    }
}
