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

namespace ApiPlatform\Doctrine\Orm\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Item state provider using the Doctrine ORM.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class ItemProvider implements ProviderInterface
{
    use LinksHandlerTrait;

    private $resourceMetadataCollectionFactory;
    private $managerRegistry;
    private $itemExtensions;

    /**
     * @param QueryItemExtensionInterface[] $itemExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ManagerRegistry $managerRegistry, iterable $itemExtensions = [])
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->managerRegistry = $managerRegistry;
        $this->itemExtensions = $itemExtensions;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $resourceClass = $operation->getClass();
        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData) {
            return $manager->getReference($resourceClass, $uriVariables);
        }

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();

        $this->handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, $context, $resourceClass, $operation);

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $uriVariables, $operation, $context);

            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operation, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operation, $context);
            }
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
