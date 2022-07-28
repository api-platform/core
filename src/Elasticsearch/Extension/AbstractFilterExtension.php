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

namespace ApiPlatform\Elasticsearch\Extension;

use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;

/**
 * Abstract class for easing the implementation of a filter extension.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractFilterExtension implements RequestBodySearchCollectionExtensionInterface
{
    public function __construct(private readonly ContainerInterface $filterLocator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(array $requestBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $resourceFilters = $operation?->getFilters();

        if (!$resourceFilters) {
            return $requestBody;
        }

        $context['filters'] ??= [];
        $clauseBody = [];

        foreach ($resourceFilters as $filterId) {
            if ($this->filterLocator->has($filterId) && is_a($filter = $this->filterLocator->get($filterId), $this->getFilterInterface())) {
                $clauseBody = $filter->apply($clauseBody, $resourceClass, $operation, $context);
            }
        }

        if (!$clauseBody) {
            return $requestBody;
        }

        return $this->alterRequestBody($requestBody, $clauseBody);
    }

    /**
     * Gets the related filter interface.
     */
    abstract protected function getFilterInterface(): string;

    /**
     * Alters the request body.
     */
    abstract protected function alterRequestBody(array $requestBody, array $clauseBody): array;
}
