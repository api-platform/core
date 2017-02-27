<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Util;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationHelper
{
    private $requestStack;
    private $resourceMetadataFactory;
    private $resourceMetadata;

    private $enabled = true;
    private $clientEnabled = false;
    private $clientItemsPerPage = false;
    private $itemsPerPage = 30;
    private $maximumItemsPerPage = null;

    private $parameterNamePage = 'page';
    private $parameterNameEnabled = 'pagination';
    private $parameterNameItemsPerPage = 'itemsPerPage';

    private $resourceClass;
    private $operationName;

    public function __construct(RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory, string $resourceClass, string $operationName = null, bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $parameterNamePage = 'page', string $parameterNameEnabled = 'pagination', string $parameterNameItemsPerPage = 'itemsPerPage', int $maximumItemsPerPage = null)
    {
        $this->requestStack = $requestStack;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        $this->enabled = $enabled;
        $this->clientEnabled = $clientEnabled;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->maximumItemsPerPage = $maximumItemsPerPage;

        $this->parameterNamePage = $parameterNamePage;
        $this->parameterNameEnabled = $parameterNameEnabled;
        $this->parameterNameItemsPerPage = $parameterNameItemsPerPage;

        $this->resourceClass = $resourceClass;
        $this->operationName = $operationName;
    }

    /**
     * @return bool
     */
    public function isResourcePaginationEnabled(): bool
    {
        $enabled = $this->getResourceMetadataAttribute('pagination_enabled', $this->enabled, true);
        $clientEnabled = $this->getResourceMetadataAttribute('pagination_client_enabled', $this->clientEnabled, true);

        return $enabled || $clientEnabled;
    }

    /**
     * @return bool
     */
    public function isPaginationEnabled(): bool
    {
        $enabled = $this->getResourceMetadataAttribute('pagination_enabled', $this->enabled, true);
        $clientEnabled = $this->getResourceMetadataAttribute('pagination_client_enabled', $this->clientEnabled, true);

        if ($clientEnabled) {
            $enabled = filter_var($this->getRequestParameter($this->parameterNameEnabled, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $enabled;
    }

    /**
     * @return float
     */
    public function getItemsPerPage(): float
    {
        $itemsPerPage = $this->getResourceMetadataAttribute('pagination_items_per_page', $this->itemsPerPage, true);
        $clientItemsPerPage = $this->getResourceMetadataAttribute('pagination_client_items_per_page', $this->clientItemsPerPage, true);
        $maximumItemsPerPage = $this->getResourceMetadataAttribute('maximum_items_per_page', $this->maximumItemsPerPage, true);

        if ($clientItemsPerPage) {
            $itemsPerPage = (float) $this->getRequestParameter($this->parameterNameItemsPerPage, $itemsPerPage);

            if (null !== $this->maximumItemsPerPage && $itemsPerPage >= $maximumItemsPerPage) {
                $itemsPerPage = $maximumItemsPerPage;
            }
        }

        return (float) $itemsPerPage;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        $page = (int) $this->getRequestParameter($this->parameterNamePage, 1);

        if ($page < 1) {
            $page = 1;
        }

        return $page;
    }

    /**
     * @return bool
     */
    public function isClientPaginationEnabled(): bool
    {
        return (bool) $this->getResourceMetadataAttribute('client_pagination_enabled', $this->clientEnabled, true);
    }

    /**
     * @return bool
     */
    public function isClientItemsPerPageEnabled(): bool
    {
        return (bool) $this->getResourceMetadataAttribute('pagination_client_items_per_page', $this->clientItemsPerPage, true);
    }

    /**
     * @return int
     */
    public function getMaximumItemsPerPage(): int
    {
        return (int) $this->getResourceMetadataAttribute('maximum_items_per_page', $this->maximumItemsPerPage, true);
    }

    /**
     * @return string
     */
    public function getParameterNamePage(): string
    {
        return $this->parameterNamePage;
    }

    /**
     * @return string
     */
    public function getParameterNameEnabled(): string
    {
        return $this->parameterNameEnabled;
    }

    /**
     * @return string
     */
    public function getParameterNameItemsPerPage(): string
    {
        return $this->parameterNameItemsPerPage;
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return string
     */
    private function getRequestParameter(string $key, $default = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $default;
        }

        return $request->query->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $defaultValue
     * @param bool   $resourceFallback
     *
     * @return mixed
     */
    private function getResourceMetadataAttribute(string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        $this->resourceMetadata ?? $this->resourceMetadata = $this->resourceMetadataFactory->create($this->resourceClass);

        return $this->resourceMetadata->getCollectionOperationAttribute($this->operationName, $key, $defaultValue, $resourceFallback);
    }
}
