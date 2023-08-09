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

namespace ApiPlatform\JsonApi\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

final class JsonApiProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $decorated, private readonly string $orderParameterName = 'order')
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'] ?? null;

        if (!$request || 'jsonapi' !== $request->getRequestFormat()) {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $filters = $request->attributes->get('_api_filters', []);
        $queryParameters = $request->query->all();
        $orderParameter = $queryParameters['sort'] ?? null;

        if (
            null !== $orderParameter
            && !\is_array($orderParameter)
        ) {
            $orderParametersArray = explode(',', (string) $orderParameter);
            $transformedOrderParametersArray = [];

            foreach ($orderParametersArray as $orderParameter) {
                $sorting = 'asc';

                if ('-' === ($orderParameter[0] ?? null)) {
                    $sorting = 'desc';
                    $orderParameter = substr($orderParameter, 1);
                }

                $transformedOrderParametersArray[$orderParameter] = $sorting;
            }

            $filters[$this->orderParameterName] = $transformedOrderParametersArray;
        }

        $filterParameter = $queryParameters['filter'] ?? null;
        if (
            $filterParameter
            && \is_array($filterParameter)
        ) {
            $filters = array_merge($filterParameter, $filters);
        }

        $pageParameter = $queryParameters['page'] ?? null;
        if (
            \is_array($pageParameter)
        ) {
            $filters = array_merge($pageParameter, $filters);
        }

        [$included, $properties] = $this->transformFieldsetsParameters($queryParameters, $operation->getShortName() ?? '');

        if ($properties) {
            $request->attributes->set('_api_filter_property', $properties);
        }

        if ($included) {
            $request->attributes->set('_api_included', $included);
        }

        if ($filters) {
            $request->attributes->set('_api_filters', $filters);
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }

    private function transformFieldsetsParameters(array $queryParameters, string $resourceShortName): array
    {
        $includeParameter = $queryParameters['include'] ?? null;
        $fieldsParameter = $queryParameters['fields'] ?? null;

        $includeParameter = \is_string($includeParameter) ? explode(',', $includeParameter) : [];
        if (!$fieldsParameter) {
            return [$includeParameter, []];
        }

        $properties = [];
        $included = [];
        foreach ($fieldsParameter as $resourceType => $fields) {
            $fields = explode(',', (string) $fields);

            if ($resourceShortName === $resourceType) {
                $properties = array_merge($properties, $fields);
            } elseif (\in_array($resourceType, $includeParameter, true)) {
                $properties[$resourceType] = $fields;
                $included[] = $resourceType;
            } else {
                $properties[$resourceType] = $fields;
            }
        }

        return [$included, $properties];
    }
}
