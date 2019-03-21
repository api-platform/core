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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * Subresource data provider for the Doctrine ORM.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class SubresourceDataProvider implements SubresourceDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $collectionExtensions;
    private $itemExtensions;

    /**
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     * @param QueryItemExtensionInterface[]       $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, /* iterable */ $collectionExtensions = [], /* iterable */ $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException(sprintf('The object manager associated with the "%s" resource class cannot be retrieved.', $resourceClass));
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        if (!isset($context['identifiers'], $context['property'])) {
            throw new ResourceClassNotSupportedException('The given resource class is not a subresource.');
        }

        $queryNameGenerator = new QueryNameGenerator();

        /*
         * The following recursively translates to this pseudo-dql:
         *
         * SELECT thirdLevel WHERE thirdLevel IN (
         *   SELECT thirdLevel FROM relatedDummies WHERE relatedDummies = ? AND relatedDummies IN (
         *     SELECT relatedDummies FROM Dummy WHERE Dummy = ?
         *   )
         * )
         *
         * By using subqueries, we're forcing the SQL execution plan to go through indexes on doctrine identifiers.
         */
        $queryBuilder = $this->buildQuery($identifiers, $context, $queryNameGenerator, $repository->createQueryBuilder($alias = 'o'), $alias, \count($context['identifiers']));

        if (true === $context['collection']) {
            foreach ($this->collectionExtensions as $extension) {
                // We don't need this anymore because we already made sub queries to ensure correct results
                if ($extension instanceof FilterEagerLoadingExtension) {
                    continue;
                }

                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
                if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
                }
            }
        } else {
            foreach ($this->itemExtensions as $extension) {
                $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
                if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
                }
            }
        }

        $query = $queryBuilder->getQuery();

        return $context['collection'] ? $query->getResult() : $query->getOneOrNullResult();
    }

    /**
     * @throws RuntimeException
     */
    private function buildQuery(array $identifiers, array $context, QueryNameGenerator $queryNameGenerator, QueryBuilder $previousQueryBuilder, string $previousAlias, int $remainingIdentifiers, QueryBuilder $topQueryBuilder = null): QueryBuilder
    {
        if ($remainingIdentifiers <= 0) {
            return $previousQueryBuilder;
        }

        $topQueryBuilder = $topQueryBuilder ?? $previousQueryBuilder;

        [$identifier, $identifierResourceClass] = $context['identifiers'][$remainingIdentifiers - 1];
        $previousAssociationProperty = $context['identifiers'][$remainingIdentifiers][0] ?? $context['property'];

        $manager = $this->managerRegistry->getManagerForClass($identifierResourceClass);

        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException("The manager for $identifierResourceClass must be an EntityManager.");
        }

        $classMetadata = $manager->getClassMetadata($identifierResourceClass);

        if (!$classMetadata instanceof ClassMetadataInfo) {
            throw new RuntimeException(
                "The class metadata for $identifierResourceClass must be an instance of ClassMetadataInfo."
            );
        }

        $qb = $manager->createQueryBuilder();
        $alias = $queryNameGenerator->generateJoinAlias($identifier);
        $normalizedIdentifiers = [];

        if (isset($identifiers[$identifier])) {
            // if it's an array it's already normalized, the IdentifierManagerTrait is deprecated
            if ($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false) {
                $normalizedIdentifiers = $identifiers[$identifier];
            } else {
                $normalizedIdentifiers = $this->normalizeIdentifiers($identifiers[$identifier], $manager, $identifierResourceClass);
            }
        }

        if ($classMetadata->hasAssociation($previousAssociationProperty)) {
            $relationType = $classMetadata->getAssociationMapping($previousAssociationProperty)['type'];
            switch ($relationType) {
                // MANY_TO_MANY relations need an explicit join so that the identifier part can be retrieved
                case ClassMetadataInfo::MANY_TO_MANY:
                    $joinAlias = $queryNameGenerator->generateJoinAlias($previousAssociationProperty);

                    $qb->select($joinAlias)
                        ->from($identifierResourceClass, $alias)
                        ->innerJoin("$alias.$previousAssociationProperty", $joinAlias);
                    break;
                case ClassMetadataInfo::ONE_TO_MANY:
                    $mappedBy = $classMetadata->getAssociationMapping($previousAssociationProperty)['mappedBy'];
                    $previousAlias = "$previousAlias.$mappedBy";

                    $qb->select($alias)
                        ->from($identifierResourceClass, $alias);
                    break;
                case ClassMetadataInfo::ONE_TO_ONE:
                    $association = $classMetadata->getAssociationMapping($previousAssociationProperty);
                    if (!isset($association['mappedBy'])) {
                        $qb->select("IDENTITY($alias.$previousAssociationProperty)")
                            ->from($identifierResourceClass, $alias);
                        break;
                    }
                    $mappedBy = $association['mappedBy'];
                    $previousAlias = "$previousAlias.$mappedBy";

                    $qb->select($alias)
                        ->from($identifierResourceClass, $alias);
                    break;
                default:
                    $qb->select("IDENTITY($alias.$previousAssociationProperty)")
                        ->from($identifierResourceClass, $alias);
            }
        } elseif ($classMetadata->isIdentifier($previousAssociationProperty)) {
            $qb->select($alias)
                ->from($identifierResourceClass, $alias);
        }

        // Add where clause for identifiers
        foreach ($normalizedIdentifiers as $key => $value) {
            $placeholder = $queryNameGenerator->generateParameterName($key);
            $qb->andWhere("$alias.$key = :$placeholder");
            $topQueryBuilder->setParameter($placeholder, $value, (string) $classMetadata->getTypeOfField($key));
        }

        // Recurse queries
        $qb = $this->buildQuery($identifiers, $context, $queryNameGenerator, $qb, $alias, --$remainingIdentifiers, $topQueryBuilder);

        return $previousQueryBuilder->andWhere($qb->expr()->in($previousAlias, $qb->getDQL()));
    }
}
