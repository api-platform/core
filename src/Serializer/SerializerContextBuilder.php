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
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $key = $normalization ? 'normalization_context' : 'denormalization_context';

        $operationKey = null;
        $operationType = null;

        if (isset($attributes['collection_operation_name'])) {
            $operationKey = 'collection_operation_name';
            $operationType = OperationType::COLLECTION;
        } elseif (isset($attributes['subresource_operation_name'])) {
            $operationKey = 'subresource_operation_name';
            $operationType = OperationType::SUBRESOURCE;
        }

        if (null !== $operationKey) {
            $attribute = $attributes[$operationKey];
            $context = $resourceMetadata->getCollectionOperationAttribute($attribute, $key, [], true);
            $context[$operationKey] = $attribute;
        } else {
            $context = $resourceMetadata->getItemOperationAttribute($attributes['item_operation_name'], $key, [], true);
            $context['item_operation_name'] = $attributes['item_operation_name'];
        }

        $context['operation_type'] = $operationType ?: OperationType::ITEM;

        if (!$normalization && !isset($context['api_allow_update'])) {
            $context['api_allow_update'] = \in_array($request->getMethod(), ['PUT', 'PATCH'], true);
        }

        $context['resource_class'] = $attributes['resource_class'];
        $context['input_class'] = $attributes['input_class'] ?? $attributes['resource_class'];
        $context['output_class'] = $attributes['output_class'] ?? $attributes['resource_class'];
        $context['request_uri'] = $request->getRequestUri();
        $context['uri'] = $request->getUri();

        if (isset($attributes['subresource_context'])) {
            $context['subresource_identifiers'] = [];

            foreach ($attributes['subresource_context']['identifiers'] as $key => list($id, $resourceClass)) {
                if (!isset($context['subresource_resources'][$resourceClass])) {
                    $context['subresource_resources'][$resourceClass] = [];
                }

                $context['subresource_identifiers'][$id] = $context['subresource_resources'][$resourceClass][$id] = $request->attributes->get($id);
            }
        }

        if (isset($attributes['subresource_property'])) {
            $context['subresource_property'] = $attributes['subresource_property'];
            $context['subresource_resource_class'] = $attributes['subresource_resource_class'] ?? null;
        }

        unset($context[DocumentationNormalizer::SWAGGER_DEFINITION_NAME]);

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromResourceClass(array $options, string $resourceClass): array
    {
        if (isset($options['serializer_groups'])) {
            return [$options['serializer_groups'], $options['serializer_groups']];
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (isset($options['collection_operation_name'])) {
            $normalizationContext = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'denormalization_context', null, true);
        } elseif (isset($options['item_operation_name'])) {
            $normalizationContext = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'denormalization_context', null, true);
        } elseif (isset($options['graphql_operation_name'])) {
            $normalizationContext = $resourceMetadata->getGraphqlAttribute($options['graphql_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getGraphqlAttribute($options['graphql_operation_name'], 'denormalization_context', null, true);
        } else {
            $normalizationContext = $resourceMetadata->getAttribute('normalization_context');
            $denormalizationContext = $resourceMetadata->getAttribute('denormalization_context');
        }

        return [$normalizationContext['groups'] ?? null, $denormalizationContext['groups'] ?? null];
    }

    /**
     * Gets the context for the property name factory.
     */
    public function getPropertyNameCollectionFactoryContext(ResourceMetadata $resourceMetadata): array
    {
        $attributes = $resourceMetadata->getAttributes();
        $context = [];

        if (isset($attributes['normalization_context'][AbstractNormalizer::GROUPS])) {
            $context['serializer_groups'] = $attributes['normalization_context'][AbstractNormalizer::GROUPS];
        }

        if (isset($attributes['denormalization_context'][AbstractNormalizer::GROUPS])) {
            if (isset($context['serializer_groups'])) {
                foreach ($attributes['denormalization_context'][AbstractNormalizer::GROUPS] as $groupName) {
                    $context['serializer_groups'][] = $groupName;
                }
            } else {
                $context['serializer_groups'] = $attributes['denormalization_context'][AbstractNormalizer::GROUPS];
            }
        }

        return $context;
    }
}
