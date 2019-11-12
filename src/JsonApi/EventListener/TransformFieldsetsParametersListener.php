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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

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

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

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
