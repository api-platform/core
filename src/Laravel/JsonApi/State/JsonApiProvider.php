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

namespace ApiPlatform\Laravel\JsonApi\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

/**
 * This is a copy of ApiPlatform\JsonApi\State\JsonApiProvider without the support of sort,filter and fields as these should be implemented using QueryParameters and specific Filters.
 * At some point we want to merge both classes but for now we don't have the SortFilter inside Symfony.
 *
 * @internal
 */
final class JsonApiProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $decorated)
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

        $pageParameter = $queryParameters['page'] ?? null;
        if (
            \is_array($pageParameter)
        ) {
            $filters = array_merge($pageParameter, $filters);
        }

        if (isset($pageParameter['offset'])) {
            $filters['page'] = $pageParameter['offset'];
            unset($filters['offset']);
        }

        $includeParameter = $queryParameters['include'] ?? null;

        if ($includeParameter) {
            $request->attributes->set('_api_included', explode(',', $includeParameter));
        }

        if ($filters) {
            $request->attributes->set('_api_filters', $filters);
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
