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
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
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
    private $resourceMetadataFactory;
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

            $requestStack = $resourceMetadataFactory;
            $resourceMetadataFactory = $pagination;

            $legacyPaginationArgs = [
                3 => ['arg_name' => 'enabled', 'option_name' => 'enabled', 'type' => 'bool', 'default' => true],
                4 => ['arg_name' => 'clientEnabled', 'option_name' => 'client_enabled', 'type' => 'bool', 'default' => false],
                5 => ['arg_name' => 'clientItemsPerPage', 'option_name' => 'client_items_per_page', 'type' => 'bool', 'default' => false],
                6 => ['arg_name' => 'itemsPerPage', 'option_name' => 'items_per_page', 'type' => 'int', 'default' => 30],
                7 => ['arg_name' => 'pageParameterName', 'option_name' => 'page_parameter_name', 'type' => 'string', 'default' => 'page'],
                8 => ['arg_name' => 'enabledParameterName', 'option_name' => 'enabled_parameter_name', 'type' => 'string', 'default' => 'pagination'],
                9 => ['arg_name' => 'itemsPerPageParameterName', 'option_name' => 'items_per_page_parameter_name', 'type' => 'string', 'default' => 'itemsPerPage'],
                10 => ['arg_name' => 'maximumItemPerPage', 'option_name' => 'maximum_items_per_page', 'type' => 'int', 'default' => null],
                11 => ['arg_name' => 'partial', 'option_name' => 'partial', 'type' => 'bool', 'default' => false],
                12 => ['arg_name' => 'clientPartial', 'option_name' => 'client_partial', 'type' => 'bool', 'default' => false],
                13 => ['arg_name' => 'partialParameterName', 'option_name' => 'partial_parameter_name', 'type' => 'string', 'default' => 'partial'],
            ];

            $paginationOptions = array_column($legacyPaginationArgs, 'default', 'option_name');

            if (0 < \count($legacyArgs = \array_slice(\func_get_args(), 3, null, true))) {
                @trigger_error(sprintf('Passing "$%s" arguments is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Pass an instance of "%s" as third argument instead.', implode('", "$', array_column($legacyPaginationArgs, 'arg_name')), Paginator::class), E_USER_DEPRECATED);

                foreach ($legacyArgs as $pos => $arg) {
                    [
                        'arg_name' => $argName,
                        'option_name' => $optionName,
                        'type' => $type,
                        'default' => $default,
                    ] = $legacyPaginationArgs[$pos];

                    if (!((null === $default && null === $arg) || \call_user_func("is_{$type}", $arg))) {
                        throw new InvalidArgumentException(sprintf('The "$%s" argument is expected to be a %s%s.', $argName, $type, null === $default ? ' or null' : ''));
                    }

                    $paginationOptions[$optionName] = $arg;
                }
            }

            $pagination = new Pagination($requestStack, $resourceMetadataFactory, $paginationOptions);
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
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        if (!$this->pagination->isEnabled($resourceClass, $operationName)) {
            return;
        }

        if (0 > $limit = $this->pagination->getLimit($resourceClass, $operationName)) {
            throw new InvalidArgumentException('Limit should not be less than 0');
        }

        if (1 > $page = $this->pagination->getPage()) {
            throw new InvalidArgumentException('Page should not be less than 1');
        }

        if (0 === $limit && 1 < $page) {
            throw new InvalidArgumentException('Page should not be greater than 1 if limit is equal to 0');
        }

        $queryBuilder
            ->setFirstResult($this->pagination->getOffset($resourceClass, $operationName))
            ->setMaxResults($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->pagination->isEnabled($resourceClass, $operationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(QueryBuilder $queryBuilder, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder, $this->useFetchJoinCollection($queryBuilder, $resourceClass, $operationName));
        $doctrineOrmPaginator->setUseOutputWalkers($this->useOutputWalkers($queryBuilder));

        if ($this->pagination->isPartialEnabled($resourceClass, $operationName)) {
            return new class($doctrineOrmPaginator) extends AbstractPaginator {
            };
        }

        return new Paginator($doctrineOrmPaginator);
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
