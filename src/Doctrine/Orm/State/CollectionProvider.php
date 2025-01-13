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

use ApiPlatform\Doctrine\Common\State\LinksHandlerLocatorTrait;
use ApiPlatform\Doctrine\Common\State\ResourceTransformerLocatorTrait;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;

/**
 * Collection state provider using the Doctrine ORM.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;
    use LinksHandlerTrait;
    use ResourceTransformerLocatorTrait;
    use StateOptionsTrait;

    /**
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        ManagerRegistry $managerRegistry,
        private readonly iterable $collectionExtensions = [],
        ?ContainerInterface $handleLinksLocator = null,
        ?ContainerInterface $resourceTransformerLocator = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->handleLinksLocator = $handleLinksLocator;
        $this->resourceTransformerLocator = $resourceTransformerLocator;
        $this->managerRegistry = $managerRegistry;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $entityClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);

        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        $repository = $manager->getRepository($entityClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, ['entityClass' => $entityClass, 'operation' => $operation] + $context);
        } else {
            $this->handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, $context, $entityClass, $operation);
        }

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $entityClass, $operation, $context);

            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($entityClass, $operation, $context)) {
                $result = $extension->getResult($queryBuilder, $entityClass, $operation, $context);
                break;
            }
        }

        $result = $result ?? $queryBuilder->getQuery()->getResult();

        return match ($transformer = $this->getToResourceTransformer($operation)) {
            null => $result,
            default => array_map($transformer, iterator_to_array($result)),
        };
    }
}
