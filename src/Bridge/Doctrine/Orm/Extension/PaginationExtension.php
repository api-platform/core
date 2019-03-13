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
use ApiPlatform\Core\DataProvider\Pagination;
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
    private $pagination;

    /**
     * @param ResourceMetadataFactoryInterface|RequestStack $resourceMetadataFactory
     * @param Pagination|ResourceMetadataFactoryInterface   $pagination
     */
    public function __construct(ManagerRegistry $managerRegistry, /* ResourceMetadataFactoryInterface */ $resourceMetadataFactory, /* Pagination */ $pagination)
    {
        if ($resourceMetadataFactory instanceof RequestStack && $pagination instanceof ResourceMetadataFactoryInterface) {
            @trigger_error(sprintf('Passing an instance of "%s" as second argument of "%s" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "%s" instead.', RequestStack::class, self::class, ResourceMetadataFactoryInterface::class), E_USER_DEPRECATED);
            @trigger_error(sprintf('Passing an instance of "%s" as third argument of "%s" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "%s" instead.', ResourceMetadataFactoryInterface::class, self::class, Pagination::class), E_USER_DEPRECATED);

            $this->requestStack = $resourceMetadataFactory;
            $resourceMetadataFactory = $pagination;
            $pagination = null;

            $args = \array_slice(\func_get_args(), 3);
            $legacyPaginationArgs = [
                ['arg_name' => 'enabled', 'type' => 'bool', 'default' => true],
                ['arg_name' => 'clientEnabled', 'type' => 'bool', 'default' => false],
                ['arg_name' => 'clientItemsPerPage', 'type' => 'bool', 'default' => false],
                ['arg_name' => 'itemsPerPage', 'type' => 'int', 'default' => 30],
                ['arg_name' => 'pageParameterName', 'type' => 'string', 'default' => 'page'],
                ['arg_name' => 'enabledParameterName', 'type' => 'string', 'default' => 'pagination'],
                ['arg_name' => 'itemsPerPageParameterName', 'type' => 'string', 'default' => 'itemsPerPage'],
                ['arg_name' => 'maximumItemPerPage', 'type' => 'int', 'default' => null],
                ['arg_name' => 'partial', 'type' => 'bool', 'default' => false],
                ['arg_name' => 'clientPartial', 'type' => 'bool', 'default' => false],
                ['arg_name' => 'partialParameterName', 'type' => 'string', 'default' => 'partial'],
            ];

            foreach ($legacyPaginationArgs as $pos => $arg) {
                if (\array_key_exists($pos, $args)) {
                    @trigger_error(sprintf('Passing "$%s" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "%s" as third argument instead.', implode('", "$', array_column($legacyPaginationArgs, 'arg_name')), Paginator::class), E_USER_DEPRECATED);

                    if (!((null === $arg['default'] && null === $args[$pos]) || \call_user_func("is_{$arg['type']}", $args[$pos]))) {
                        throw new InvalidArgumentException(sprintf('The "$%s" argument is expected to be a %s%s.', $arg['arg_name'], $arg['type'], null === $arg['default'] ? ' or null' : ''));
                    }

                    $value = $args[$pos];
                } else {
                    $value = $arg['default'];
                }

                $this->{$arg['arg_name']} = $value;
            }
        } elseif (!$resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            throw new InvalidArgumentException(sprintf('The "$resourceMetadataFactory" argument is expected to be an implementation of the "%s" interface.', ResourceMetadataFactoryInterface::class));
        } elseif (!$pagination instanceof Pagination) {
            throw new InvalidArgumentException(sprintf('The "$pagination" argument is expected to be an instance of the "%s" class.', Pagination::class));
        }

        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (null === $pagination = $this->getPagination($queryBuilder, $resourceClass, $operationName, $context)) {
            return;
        }

        [$offset, $limit] = $pagination;

        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        if (null === $this->requestStack) {
            return $this->pagination->isEnabled($resourceClass, $operationName, $context);
        }

        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return false;
        }

        return $this->isPaginationEnabled($request, $this->resourceMetadataFactory->create($resourceClass), $operationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder, $this->useFetchJoinCollection($queryBuilder, $resourceClass, $operationName));
        $doctrineOrmPaginator->setUseOutputWalkers($this->useOutputWalkers($queryBuilder));

        if (null === $this->requestStack) {
            $isPartialEnabled = $this->pagination->isPartialEnabled($resourceClass, $operationName, $context);
        } else {
            $isPartialEnabled = $this->isPartialPaginationEnabled(
                $this->requestStack->getCurrentRequest(),
                null === $resourceClass ? null : $this->resourceMetadataFactory->create($resourceClass),
                $operationName
            );
        }

        if ($isPartialEnabled) {
            return new class($doctrineOrmPaginator) extends AbstractPaginator {
            };
        }

        return new Paginator($doctrineOrmPaginator);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getPagination(QueryBuilder $queryBuilder, string $resourceClass, ?string $operationName, array $context): ?array
    {
        $request = null;
        if (null !== $this->requestStack && null === $request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        if (null === $request) {
            if (!$this->pagination->isEnabled($resourceClass, $operationName, $context)) {
                return null;
            }

            $context = $this->addCountToContext($queryBuilder, $context);

            return \array_slice($this->pagination->getPagination($resourceClass, $operationName, $context), 1);
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (!$this->isPaginationEnabled($request, $resourceMetadata, $operationName)) {
            return null;
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

        $page = (int) $this->getPaginationParameter($request, $this->pageParameterName, 1);

        if (1 > $page) {
            throw new InvalidArgumentException('Page should not be less than 1');
        }

        if (0 === $itemsPerPage && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if itemsPerPage is equal to 0');
        }

        $firstResult = ($page - 1) * $itemsPerPage;
        if ($request->attributes->get('_graphql')) {
            $collectionArgs = $request->attributes->get('_graphql_collections_args', []);
            if (isset($collectionArgs[$resourceClass]['after'])) {
                $after = base64_decode($collectionArgs[$resourceClass]['after'], true);
                $firstResult = (int) $after;
                $firstResult = false === $after ? $firstResult : ++$firstResult;
            }
        }

        return [$firstResult, $itemsPerPage];
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

    private function getPaginationParameter(Request $request, string $parameterName, $default = null)
    {
        if (null !== $paginationAttribute = $request->attributes->get('_api_pagination')) {
            return \array_key_exists($parameterName, $paginationAttribute) ? $paginationAttribute[$parameterName] : $default;
        }

        return $request->query->get($parameterName, $default);
    }

    private function addCountToContext(QueryBuilder $queryBuilder, array $context): array
    {
        if (!($context['graphql'] ?? false)) {
            return $context;
        }

        if (isset($context['filters']['last']) && !isset($context['filters']['before'])) {
            $context['count'] = (new DoctrineOrmPaginator($queryBuilder))->count();
        }

        return $context;
    }

    /**
     * Determines whether the Paginator should fetch join collections, if the root entity uses composite identifiers it should not.
     *
     * @see https://github.com/doctrine/doctrine2/issues/2910
     */
    private function useFetchJoinCollection(QueryBuilder $queryBuilder, string $resourceClass = null, string $operationName = null): bool
    {
        if (QueryChecker::hasRootEntityWithCompositeIdentifier($queryBuilder, $this->managerRegistry)) {
            return false;
        }

        if (null === $resourceClass) {
            return true;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        return $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_fetch_join_collection', true, true);
    }

    /**
     * Determines whether output walkers should be used.
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
}
