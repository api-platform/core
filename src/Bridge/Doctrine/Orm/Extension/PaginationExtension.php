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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\AbstractPaginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies pagination on the Doctrine query for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PaginationExtension implements ContextAwareQueryResultCollectionExtensionInterface
{
    private $managerRegistry;
    private $requestStack;
    private $resourceMetadataFactory;
    private $enabled;
    private $clientEnabled;
    private $clientItemsPerPage;
    private $itemsPerPage;
    private $pageParameterName;
    private $enabledParameterName;
    private $itemsPerPageParameterName;
    private $maximumItemPerPage;
    private $partial;
    private $clientPartial;
    private $partialParameterName;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory, bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $pageParameterName = 'page', string $enabledParameterName = 'pagination', string $itemsPerPageParameterName = 'itemsPerPage', int $maximumItemPerPage = null, bool $partial = false, bool $clientPartial = false, string $partialParameterName = 'partial')
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->enabled = $enabled;
        $this->clientEnabled = $clientEnabled;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->pageParameterName = $pageParameterName;
        $this->enabledParameterName = $enabledParameterName;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
        $this->maximumItemPerPage = $maximumItemPerPage;
        $this->partial = $partial;
        $this->clientPartial = $clientPartial;
        $this->partialParameterName = $partialParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (!$this->isPaginationEnabled($request, $resourceMetadata, $operationName)) {
            return;
        }

        $itemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_items_per_page', $this->itemsPerPage, true);
        if ($request->attributes->get('_graphql')) {
            $collectionArgs = $request->attributes->get('_graphql_collections_args', []);
            $itemsPerPage = $collectionArgs[$resourceClass]['first'] ?? $itemsPerPage;
        }

        if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
            $maxItemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'maximum_items_per_page', $this->maximumItemPerPage, true);

            $itemsPerPage = (int) $this->getPaginationParameter($request, $this->itemsPerPageParameterName, $itemsPerPage);
            $itemsPerPage = (null !== $maxItemsPerPage && $itemsPerPage >= $maxItemsPerPage ? $maxItemsPerPage : $itemsPerPage);
        }

        if (0 > $itemsPerPage) {
            throw new InvalidArgumentException('Item per page parameter should not be less than 0');
        }

        $page = $this->getPaginationParameter($request, $this->pageParameterName, 1);

        if (0 === $itemsPerPage && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if itemsPegPage is equal to 0');
        }

        $firstResult = ($page - 1) * $itemsPerPage;
        if ($request->attributes->get('_graphql')) {
            $collectionArgs = $request->attributes->get('_graphql_collections_args', []);
            if (isset($collectionArgs[$resourceClass]['after'])) {
                $after = \base64_decode($collectionArgs[$resourceClass]['after'], true);
                $firstResult = (int) $after;
                $firstResult = false === $after ? $firstResult : ++$firstResult;
            }
        }

        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        return $this->isPaginationEnabled($request, $resourceMetadata, $operationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder, $this->useFetchJoinCollection($queryBuilder));
        $doctrineOrmPaginator->setUseOutputWalkers($this->useOutputWalkers($queryBuilder));

        $resourceMetadata = null === $resourceClass ? null : $this->resourceMetadataFactory->create($resourceClass);

        if ($this->isPartialPaginationEnabled($this->requestStack->getCurrentRequest(), $resourceMetadata, $operationName)) {
            return new class($doctrineOrmPaginator) extends AbstractPaginator {
            };
        }

        return new Paginator($doctrineOrmPaginator);
    }

    private function isPartialPaginationEnabled(Request $request = null, ResourceMetadata $resourceMetadata = null, string $operationName = null): bool
    {
        $enabled = $this->partial;
        $clientEnabled = $this->clientPartial;

        if ($resourceMetadata) {
            $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_partial', $enabled, true);

            if ($request) {
                $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_partial', $clientEnabled, true);
            }
        }

        if ($clientEnabled && $request) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->partialParameterName, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    private function isPaginationEnabled(Request $request, ResourceMetadata $resourceMetadata, string $operationName = null): bool
    {
        $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', $this->enabled, true);
        $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->clientEnabled, true);

        if ($clientEnabled) {
            $enabled = filter_var($this->getPaginationParameter($request, $this->enabledParameterName, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }

    /**
     * Determines whether the Paginator should fetch join collections, if the root entity uses composite identifiers it should not.
     *
     * @see https://github.com/doctrine/doctrine2/issues/2910
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    private function useFetchJoinCollection(QueryBuilder $queryBuilder): bool
    {
        return !QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry);
    }

    /**
     * Determines whether output walkers should be used.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    private function useOutputWalkers(QueryBuilder $queryBuilder): bool
    {
        /*
         * "Cannot count query that uses a HAVING clause. Use the output walkers for pagination"
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/CountWalker.php#L50
         */
        if (QueryChecker::hasHavingClause($queryBuilder)) {
            return true;
        }

        /*
         * "Paginating an entity with foreign key as identifier only works when using the Output Walkers. Call Paginator#setUseOutputWalkers(true) before iterating the paginator."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L87
         */
        if (QueryChecker::hasRootEntityWithForeignKeyIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        /*
         * "Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a fetch joined to-many association. Use output walkers."
         *
         * @see https://github.com/doctrine/doctrine2/blob/900b55d16afdcdeb5100d435a7166d3a425b9873/lib/Doctrine/ORM/Tools/Pagination/LimitSubqueryWalker.php#L149
         */
        if (
            QueryChecker::hasMaxResults($queryBuilder) &&
            QueryChecker::hasOrderByOnToManyJoin($queryBuilder, $this->managerRegistry)
        ) {
            return true;
        }

        /*
         * When using composite identifiers pagination will need Output walkers
         */
        if (QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry)) {
            return true;
        }

        // Disable output walkers by default (performance)
        return false;
    }

    private function getPaginationParameter(Request $request, string $parameterName, $default = null)
    {
        if (null !== $paginationAttribute = $request->attributes->get('_api_pagination')) {
            return array_key_exists($parameterName, $paginationAttribute) ? $paginationAttribute[$parameterName] : $default;
        }

        return $request->query->get($parameterName, $default);
    }
}
