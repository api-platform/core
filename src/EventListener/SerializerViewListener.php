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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerViewListener
{
    private $serializer;
    private $resourceMetadataFactory;

    public function __construct(SerializerInterface $serializer, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->serializer = $serializer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Serializes the data to the requested format.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $format = $request->attributes->get('_api_format');

        if ($controllerResult instanceof Response || !$format) {
            return;
        }

        try {
            list($resourceClass, $collectionOperationName, $itemOperationName, $format) = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if ($collectionOperationName) {
            $context = $resourceMetadata->getCollectionOperationAttribute($collectionOperationName, 'normalization_context', [], true);
            $context['collection_operation_name'] = $collectionOperationName;
        } else {
            $context = $resourceMetadata->getItemOperationAttribute($itemOperationName, 'normalization_context', [], true);
            $context['item_operation_name'] = $itemOperationName;
        }

        $context['resource_class'] = $resourceClass;
        $context['request_uri'] = $request->getRequestUri();

        $event->setControllerResult($this->serializer->serialize($controllerResult, $format, $context));
    }
}
