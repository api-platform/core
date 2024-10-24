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

use ApiPlatform\Doctrine\Common\ParameterValueExtractorTrait;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Reads operation parameters and execute its filter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ParameterExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    use ParameterValueExtractorTrait;

    public function __construct(
        private readonly ContainerInterface $filterLocator,
        private readonly ?ManagerRegistry $managerRegistry = null,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    private function applyFilter(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $filter = null;

        foreach ($operation?->getParameters() ?? [] as $parameter) {
            if (($v = $parameter->getValue()) === null || $v instanceof ParameterNotFound) {
                continue;
            }

            $values = $this->extractParameterValue($parameter, $v);
            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            if (\is_string($filterId) && $this->filterLocator->has($filterId)) {
                $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
            }

            if (\is_object($filterId)) {
                $filter = $filterId;
                $filter->setManagerRegistry($this->managerRegistry);
                $filter->setProperties($values);
            }

            if ($filter instanceof FilterInterface) {
                $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation,
                    array_merge(['filters' => $values, 'parameter' => $parameter], $context)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
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
