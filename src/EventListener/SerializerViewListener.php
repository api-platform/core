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

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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

        $resourceClass = $request->attributes->get('_resource_class');
        $collectionOperationName = $request->attributes->get('_collection_operation_name');
        $itemOperationName = $request->attributes->get('_item_operation_name');

        if (!$resourceClass || (!$collectionOperationName && !$itemOperationName)) {
            return;
        }

        $context = ['request_uri' => $request->getRequestUri(), 'resource_class' => $resourceClass];
        if ($collectionOperationName) {
            $context['collection_operation_name'] = $collectionOperationName;
        } else {
            $context['item_operation_name'] = $itemOperationName;
        }

        $event->setControllerResult($this->serializer->serialize($controllerResult, $format, $context));
    }
}
