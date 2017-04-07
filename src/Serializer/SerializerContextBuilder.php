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
        if (null === $attributes) {
            $attributes = RequestAttributesExtractor::extractAttributes($request);
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $key = $normalization ? 'normalization_context' : 'denormalization_context';

        if (isset($attributes['collection_operation_name'])) {
            $context = $resourceMetadata->getCollectionOperationAttribute($attributes['collection_operation_name'], $key, [], true);
            $context['collection_operation_name'] = $attributes['collection_operation_name'];
        } else {
            $context = $resourceMetadata->getItemOperationAttribute($attributes['item_operation_name'], $key, [], true);
            $context['item_operation_name'] = $attributes['item_operation_name'];
        }

        if (!$normalization && !isset($context['api_allow_update'])) {
            $context['api_allow_update'] = Request::METHOD_PUT === $request->getMethod();
        }

        $context['resource_class'] = $attributes['resource_class'];
        $context['request_uri'] = $request->getRequestUri();

        return $context;
    }
}
