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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    private $resourceMetadataFactory;

    public function __construct($resourceMetadataFactory = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be not be used anymore in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        if (
            (!$this->resourceMetadataFactory || $this->resourceMetadataFactory instanceof ResourceCollectionMetadataFactoryInterface)
            && isset($attributes['operation_name'])
        ) {
            try {
                $context = $attributes['operation'] + ($normalization ? $attributes['operation']['normalization_context'] : $attributes['operation']['denormalization_context']);
                $context['operation_name'] = $attributes['operation_name'];
                $context['resource_class'] = $attributes['resource_class'];
                // TODO: 3.0 becomes true by default
                $context['skip_null_values'] = $context['skip_null_values'] ?? $this->shouldSkipNullValues($attributes['resource_class'], $attributes['operation_name']);
                // TODO: remove in 3.0, operation type will not exist anymore
                $context['operation_type'] = $attributes['operation']['collection'] ? OperationType::ITEM : OperationType::COLLECTION;
                $context['iri_only'] = $context['iri_only'] ?? false;
                $context['request_uri'] = $request->getRequestUri();
                $context['uri'] = $request->getUri();

                if (!$normalization) {
                    if (!isset($context['api_allow_update'])) {
                        $context['api_allow_update'] = \in_array($method = $request->getMethod(), ['PUT', 'PATCH'], true);

                        if ($context['api_allow_update'] && 'PATCH' === $method) {
                            $context['deep_object_to_populate'] = $context['deep_object_to_populate'] ?? true;
                        }
                    }

                    if ('csv' === $request->getContentType()) {
                        $context[CsvEncoder::AS_COLLECTION_KEY] = false;
                    }
                }

                return $context;
            } catch (ResourceClassNotFoundException $e) {
            }
        }

        // TODO: remove in 3.0
        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $key = $normalization ? 'normalization_context' : 'denormalization_context';

        if ($resourceMetadata instanceof ResourceCollection) {
            $key = $normalization ? 'normalizationContext' : 'denormalizationContext';
        }

        if (isset($attributes['collection_operation_name'])) {
            $operationKey = 'collection_operation_name';
            $operationType = OperationType::COLLECTION;
        } elseif (isset($attributes['item_operation_name'])) {
            $operationKey = 'item_operation_name';
            $operationType = OperationType::ITEM;
        } else {
            $operationKey = 'subresource_operation_name';
            $operationType = OperationType::SUBRESOURCE;
        }

        $context = $resourceMetadata instanceof ResourceMetadata ? $resourceMetadata->getTypedOperationAttribute($operationType, $attributes[$operationKey], $key, [], true) : $resourceMetadata[0]->{$key};
        $context['operation_type'] = $operationType;
        $context[$operationKey] = $attributes[$operationKey];

        if (!$normalization) {
            if (!isset($context['api_allow_update'])) {
                $context['api_allow_update'] = \in_array($method = $request->getMethod(), ['PUT', 'PATCH'], true);

                if ($context['api_allow_update'] && 'PATCH' === $method) {
                    $context['deep_object_to_populate'] = $context['deep_object_to_populate'] ?? true;
                }
            }

            if ('csv' === $request->getContentType()) {
                $context[CsvEncoder::AS_COLLECTION_KEY] = false;
            }
        }

        $context['resource_class'] = $attributes['resource_class'];
        $context['iri_only'] = $resourceMetadata instanceof ResourceMetadata ? ($resourceMetadata->getAttribute('normalization_context')['iri_only'] ?? false) : ($resourceMetadata->normalizationContext['iri_only'] ?? false);
        $context['input'] = $resourceMetadata instanceof ResourceMetadata ? $resourceMetadata->getTypedOperationAttribute($operationType, $attributes[$operationKey], 'input', null, true) : $resourceMetadata->getOperation($operationType)->input ?? null;
        $context['output'] = $resourceMetadata instanceof ResourceMetadata ? $resourceMetadata->getTypedOperationAttribute($operationType, $attributes[$operationKey], 'output', null, true) : $resourceMetadata->getOperation($operationType)->output ?? null;
        $context['request_uri'] = $request->getRequestUri();
        $context['uri'] = $request->getUri();

        if (isset($attributes['subresource_context'])) {
            $context['subresource_identifiers'] = [];

            foreach ($attributes['subresource_context']['identifiers'] as $parameterName => [$resourceClass]) {
                if (!isset($context['subresource_resources'][$resourceClass])) {
                    $context['subresource_resources'][$resourceClass] = [];
                }

                $context['subresource_identifiers'][$parameterName] = $context['subresource_resources'][$resourceClass][$parameterName] = $request->attributes->get($parameterName);
            }
        }

        if (isset($attributes['subresource_property'])) {
            $context['subresource_property'] = $attributes['subresource_property'];
            $context['subresource_resource_class'] = $attributes['subresource_resource_class'] ?? null;
        }

        unset($context[DocumentationNormalizer::SWAGGER_DEFINITION_NAME]);

        if (isset($context['skip_null_values'])) {
            return $context;
        }

        if ($resourceMetadata instanceof ResourceMetadata) {
            foreach ($resourceMetadata->getItemOperations() as $operation) {
                if ('PATCH' === ($operation['method'] ?? '') && \in_array('application/merge-patch+json', $operation['input_formats']['json'] ?? [], true)) {
                    $context['skip_null_values'] = true;

                    break;
                }
            }
        } else {
            foreach ($resourceMetadata as $resourceName => $resource) {
                foreach ($resource->operations as $operationName => $operation) {
                    if ('PATCH' === ($operation->method ?? '') && \in_array('application/merge-patch+json', $operation->inputFormats['json'] ?? [], true)) {
                        $context['skip_null_values'] = true;
                        break;
                    }
                }
            }
        }

        return $context;
    }

    /**
     * TODO: remove in 3.0, this will have no impact and skip_null_values will be default, no more resourceMetadataFactory call in this class.
     */
    private function shouldSkipNullValues(string $class, string $operationName): bool
    {
        $collection = $this->resourceMetadataFactory->create($class);
        foreach ($collection as $metadata) {
            foreach ($metadata->operations as $operation) {
                if ('PATCH' === ($operation->method ?? '') && \in_array('application/merge-patch+json', $operation->inputFormats ?? [], true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
