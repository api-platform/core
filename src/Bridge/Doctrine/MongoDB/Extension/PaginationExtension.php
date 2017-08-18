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
/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Paginator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Query\Builder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies pagination on the Doctrine query for resource collection when enabled.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class PaginationExtension implements QueryResultExtensionInterface
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

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory, bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $pageParameterName = 'page', string $enabledParameterName = 'pagination', string $itemsPerPageParameterName = 'itemsPerPage')
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
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (!$this->isPaginationEnabled($request, $resourceMetadata, $operationName)) {
            return;
        }

        $itemsPerPage = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_items_per_page', $this->itemsPerPage, true);
        if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
            $itemsPerPage = (int) $request->query->get($this->itemsPerPageParameterName, $itemsPerPage);
        }

        $queryBuilder
            ->skip(($request->query->get($this->pageParameterName, 1) - 1) * $itemsPerPage)
            ->limit($itemsPerPage);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, string $operationName = null): bool
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
    public function getResult(Builder $queryBuilder)
    {
        return new Paginator($queryBuilder->getQuery()->execute());
    }

    private function isPaginationEnabled(Request $request, ResourceMetadata $resourceMetadata, string $operationName = null): bool
    {
        $enabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', $this->enabled, true);
        $clientEnabled = $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_enabled', $this->clientEnabled, true);

        if ($clientEnabled) {
            $enabled = filter_var($request->query->get($this->enabledParameterName, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
    }
}
