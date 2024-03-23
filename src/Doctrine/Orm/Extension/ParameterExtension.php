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

namespace ApiPlatform\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;

/**
 * Reads operation parameters and execute its filter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ParameterExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly ContainerInterface $filterLocator)
    {
    }

    private function applyFilter(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): void
    {
        if (!($request = $context['request'] ?? null)) {
            return;
        }

        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        foreach ($operation->getParameters() ?? [] as $parameter) {
            $key = $parameter->getKey();
            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $parameters = $parameter instanceof HeaderParameterInterface ? $request->attributes->get('_api_header_parameters') : $request->attributes->get('_api_query_parameters');
            $parsedKey = explode('[:property]', $key);
            if (isset($parsedKey[0]) && isset($parameters[$parsedKey[0]])) {
                $key = $parsedKey[0];
            }

            if (!isset($parameters[$key])) {
                continue;
            }

            $value = $parameters[$key];
            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
            if ($filter instanceof FilterInterface) {
                $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, ['filters' => [$key => $value]] + $context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): void
    {
        $this->applyFilter($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->applyFilter($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }
}
