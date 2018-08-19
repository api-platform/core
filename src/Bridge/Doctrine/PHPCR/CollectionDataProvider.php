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

namespace ApiPlatform\Core\Bridge\Doctrine\PHPCR;

use ApiPlatform\Core\Bridge\Doctrine\PHPCR\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\PHPCR\Extension\QueryResultExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\PHPCR\Util\QueryNameGenerator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Collection data provider for the Doctrine MongoDB ODM.
 */
class CollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $managerRegistry;
    private $collectionExtensions;

    /**
     * @param QueryCollectionExtensionInterface[]|ContextAwareQueryCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, /* iterable */ $collectionExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return null !== $this->managerRegistry->getManagerForClass($resourceClass);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createAggregationBuilder')) {
            throw new RuntimeException('The repository class must have a "createAggregationBuilder" method.');
        }

        /** @var Builder $aggregationBuilder */
        $aggregationBuilder = $repository->createAggregationBuilder();
        $queryNameGenerator = new QueryNameGenerator();
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($aggregationBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

            if ($extension instanceof QueryResultExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
            }
        }

        return $aggregationBuilder->hydrate($resourceClass)->execute()->toArray();
    }
}
