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
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

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

        $key = $normalization ? 'normalization_context' : 'denormalization_context';

        if (isset($attributes['collection_operation_name'])) {
            $attribute = $attributes['collection_operation_name'];
            $resourceClass = $attributes['resource_class'];

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $context = $resourceMetadata->getCollectionOperationAttribute($attribute, $key, [], true);

            $context['collection_operation_name'] = $attribute;
            $context['resource_class'] = $resourceClass;
            $context['operation_type'] = OperationType::COLLECTION;
        } elseif (isset($attributes['subresource_operation_name'])) {
            $attribute = $attributes['subresource_operation_name'];
            $resourceClass = $attributes['resource_class'];
            $parentClass = $attributes['subresource_context']['parent_resource_class'];
            $parentOperationName = $attributes['subresource_context']['parent_operation_name'];

            $parentMetadata = $this->resourceMetadataFactory->create($parentClass);
            $context = $parentMetadata->getSubresourceOperationAttribute($parentOperationName, $key, [], true);

            $context['subresource_operation_name'] = $attribute;
            $context['resource_class'] = $resourceClass;
            $context['operation_type'] = OperationType::SUBRESOURCE;
        } else {
            $resourceClass = $attributes['resource_class'];
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $context = $resourceMetadata->getItemOperationAttribute($attributes['item_operation_name'], $key, [], true);
            $context['item_operation_name'] = $attributes['item_operation_name'];
            $context['resource_class'] = $attributes['resource_class'];
            $context['operation_type'] = OperationType::ITEM;
        }

        if (!$normalization && !isset($context['api_allow_update'])) {
            $context['api_allow_update'] = Request::METHOD_PUT === $request->getMethod();
        }

        $context['request_uri'] = $request->getRequestUri();

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

        return $context;
    }
}
