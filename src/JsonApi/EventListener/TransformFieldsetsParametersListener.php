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

namespace ApiPlatform\Core\JsonApi\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @see http://jsonapi.org/format/#fetching-sparse-fieldsets
 * @see https://api-platform.com/docs/core/filters#property-filter
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TransformFieldsetsParametersListener
{
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

        $includeParameter = $request->query->get('include');
        if (
            'jsonapi' !== $request->getRequestFormat() ||
            !($resourceClass = $request->attributes->get('_api_resource_class')) ||
            (!($fieldsParameter = $request->query->get('fields')) && !$includeParameter)
        ) {
            return;
        }

        if (
            ($fieldsParameter && !\is_array($fieldsParameter)) ||
            ($includeParameter && !\is_string($includeParameter))
        ) {
            return;
        }

        $properties = [];

        $includeParameter = explode(',', $includeParameter ?? '');

        if (!$fieldsParameter) {
            $request->attributes->set('_api_included', $includeParameter);

            return;
        }

        $resourceShortName = $this->resourceMetadataFactory->create($resourceClass)->getShortName();

        foreach ($fieldsParameter as $resourceType => $fields) {
            $fields = explode(',', $fields);

            if ($resourceShortName === $resourceType) {
                $properties = array_merge($properties, $fields);
            } elseif (\in_array($resourceType, $includeParameter, true)) {
                $properties[$resourceType] = $fields;

                $request->attributes->set('_api_included', array_merge($request->attributes->get('_api_included', []), [$resourceType]));
            } else {
                $properties[$resourceType] = $fields;
            }
        }

        $request->attributes->set('_api_filter_property', $properties);
    }
}
