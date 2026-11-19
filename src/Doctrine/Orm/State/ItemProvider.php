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
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;

/**
 * Item state provider using the Doctrine ORM.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class ItemProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;
    use LinksHandlerTrait;
    use StateOptionsTrait;

    /**
     * @param QueryItemExtensionInterface[] $itemExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ManagerRegistry $managerRegistry, private readonly iterable $itemExtensions = [], ?ContainerInterface $handleLinksLocator = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->handleLinksLocator = $handleLinksLocator;
        $this->managerRegistry = $managerRegistry;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $entityClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);

        /** @var EntityManagerInterface|null $manager */
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $manager) {
            throw new RuntimeException(\sprintf('No manager found for class "%s". Are you sure it\'s an entity?', $entityClass));
        }

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData && null !== ($identifiers = $this->getReferenceIdentifiers($manager, $entityClass, $operation, $uriVariables, $context))) {
            return $manager->getReference($entityClass, $identifiers);
        }

        $repository = $manager->getRepository($entityClass);

        if ($method = $this->getStateOptionsRepositoryMethod($operation)) {
            if (!method_exists($repository, $method)) {
                throw new RuntimeException(\sprintf('The repository method "%s::%s" does not exist.', $repository::class, $method));
            }

            $queryBuilder = $repository->{$method}();

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new RuntimeException(\sprintf('The repository method "%s" must return a %s instance.', $method, QueryBuilder::class));
            }
        } else {
            if (!method_exists($repository, 'createQueryBuilder')) {
                throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
            }

            $queryBuilder = $repository->createQueryBuilder('o');
        }

        $queryNameGenerator = new QueryNameGenerator();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, ['entityClass' => $entityClass, 'operation' => $operation] + $context);
        } else {
            $this->handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, $context, $entityClass, $operation);
        }

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $entityClass, $uriVariables, $operation, $context);

            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($entityClass, $operation, $context)) {
                return $extension->getResult($queryBuilder, $entityClass, $operation, $context);
            }
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Builds the [identifierField => value] map for getReference() from the resource's own identifier
     * links, ignoring parent/relation links a subresource carries (e.g. "companyId") which are not
     * identifiers of the entity and would make getReference() throw UnrecognizedIdentifierFields.
     *
     * Returns null (so the caller falls through to the link-resolving query) when an own identifier
     * value is missing, or when a resource identifier is not a Doctrine identifier of the entity
     * (e.g. a uuid exposed as the API identifier while the table key is "id") and therefore cannot be
     * turned into a reference.
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>|null
     */
    private function getReferenceIdentifiers(EntityManagerInterface $manager, string $entityClass, Operation $operation, array $uriVariables, array $context): ?array
    {
        $identifierFields = array_flip($manager->getClassMetadata($entityClass)->getIdentifierFieldNames());

        $identifiers = [];
        foreach ($this->getLinks($entityClass, $operation, $context) as $parameterName => $link) {
            // Mirrors LinksHandlerTrait: the identifier-self link has no relation property and points to the resource itself.
            // Links are expressed in resource terms, so the identifier-self link's fromClass is the resource class
            // ($operation->getClass()), which differs from $entityClass for a DTO/stateOptions resource.
            if ($operation->getClass() !== $link->getFromClass() || $link->getFromProperty() || $link->getToProperty()) {
                continue;
            }

            $identifierProperties = $link->getIdentifiers();
            $hasCompositeIdentifiers = 1 < \count($identifierProperties);
            foreach ($identifierProperties as $identifierProperty) {
                if (!isset($identifierFields[$identifierProperty])) {
                    return null;
                }

                // Composite identifiers are exploded by field name upstream; a single identifier is keyed by its uriVariable name.
                $key = $hasCompositeIdentifiers ? $identifierProperty : $parameterName;
                if (!\array_key_exists($key, $uriVariables)) {
                    return null;
                }

                $identifiers[$identifierProperty] = $uriVariables[$key];
            }
        }

        return $identifiers ?: null;
    }
}
