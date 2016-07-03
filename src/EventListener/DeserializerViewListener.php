<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Api\RequestAttributesExtractor;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Updates the entity retrieved by the data provider with data contained in the request body.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeserializerViewListener
{
    private $serializer;
    private $resourceMetadataFactory;

    public function __construct(SerializerInterface $serializer, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->serializer = $serializer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $data = $event->getControllerResult();
        $request = $event->getRequest();

        if ($data instanceof Response || !in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            return;
        }

        try {
            list($resourceClass, $collectionOperationName, $itemOperationName, $format) = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if ($collectionOperationName) {
            $context = $resourceMetadata->getCollectionOperationAttribute($collectionOperationName, 'denormalization_context', [], true);
            $context['collection_operation_name'] = $collectionOperationName;
        } else {
            $context = $resourceMetadata->getItemOperationAttribute($itemOperationName, 'denormalization_context', [], true);
            $context['item_operation_name'] = $itemOperationName;
        }

        $context['resource_class'] = $resourceClass;
        $context['request_uri'] = $request->getRequestUri();

        if (null !== $data) {
            $context['object_to_populate'] = $data;
        }

        $event->setControllerResult($this->serializer->deserialize($request->getContent(), $resourceClass, $format, $context));
    }
}
