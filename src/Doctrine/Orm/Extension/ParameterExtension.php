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

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\ParameterValueExtractorTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function applyFilter(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        foreach ($operation?->getParameters() ?? [] as $parameter) {
            if (null === ($v = $parameter->getValue()) || $v instanceof ParameterNotFound) {
                continue;
            }

            $values = $this->extractParameterValue($parameter, $v);
            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $filter = match (true) {
                $filterId instanceof FilterInterface => $filterId,
                \is_string($filterId) && $this->filterLocator->has($filterId) => $this->filterLocator->get($filterId),
                default => null,
            };

            if (!($filter instanceof FilterInterface)) {
                throw new InvalidArgumentException(\sprintf('Could not find filter "%s" for parameter "%s" in operation "%s" for resource "%s".', $filterId, $parameter->getKey(), $operation?->getShortName(), $resourceClass));
            }

            if ($filter instanceof ManagerRegistryAwareInterface && !$filter->hasManagerRegistry()) {
                $filter->setManagerRegistry($this->managerRegistry);
            }

            if ($filter instanceof AbstractFilter && !$filter->getProperties()) {
                $filter->setProperties([$parameter->getProperty() ?? $parameter->getKey() => []]);
            }

            $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation,
                ['filters' => $values, 'parameter' => $parameter] + $context
            );
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
