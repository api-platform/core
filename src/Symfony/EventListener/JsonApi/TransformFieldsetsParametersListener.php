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

namespace ApiPlatform\Symfony\EventListener\JsonApi;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @see http://jsonapi.org/format/#fetching-sparse-fieldsets
 * @see https://api-platform.com/docs/core/filters#property-filter
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TransformFieldsetsParametersListener
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (($operation = $request->attributes->get('_api_operation')) && 'api_platform.symfony.main_controller' === $operation->getController()) {
            return;
        }

        $queryParameters = $request->query->all();
        $includeParameter = $queryParameters['include'] ?? null;
        $fieldsParameter = $queryParameters['fields'] ?? null;

        if (
            (!$fieldsParameter && !$includeParameter)
            || ($fieldsParameter && !\is_array($fieldsParameter))
            || (!\is_string($includeParameter))
            || 'jsonapi' !== $request->getRequestFormat()
            || !($resourceClass = $request->attributes->get('_api_resource_class'))
        ) {
            return;
        }

        $includeParameter = explode(',', $includeParameter);
        if (!$fieldsParameter) {
            $request->attributes->set('_api_included', $includeParameter);

            return;
        }

        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $resourceShortName = $resourceMetadata->getOperation()->getShortName();

        $properties = [];
        foreach ($fieldsParameter as $resourceType => $fields) {
            $fields = explode(',', (string) $fields);

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
