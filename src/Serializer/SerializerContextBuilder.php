<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\RequestAttributesExtractor;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
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
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null) : array
    {
        if (null === $extractedAttributes) {
            $extractedAttributes = RequestAttributesExtractor::extractAttributes($request);
        }

        list($resourceClass, $collectionOperationName, $itemOperationName) = $extractedAttributes;

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $key = $normalization ? 'normalization_context' : 'denormalization_context';

        if ($collectionOperationName) {
            $context = $resourceMetadata->getCollectionOperationAttribute($collectionOperationName, $key, [], true);
            $context['collection_operation_name'] = $collectionOperationName;
        } else {
            $context = $resourceMetadata->getItemOperationAttribute($itemOperationName, $key, [], true);
            $context['item_operation_name'] = $itemOperationName;
        }

        $context['resource_class'] = $resourceClass;
        $context['request_uri'] = $request->getRequestUri();

        return $context;
    }
}
