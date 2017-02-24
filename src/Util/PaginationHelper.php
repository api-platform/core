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
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Exception;
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

    /**
     * @param RequestStack                     $requestStack
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     */
    public function __construct(RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->requestStack = $requestStack;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @param bool     $enabled
     * @param bool     $clientEnabled
     * @param bool     $clientItemsPerPage
     * @param int      $itemsPerPage
     * @param string   $parameterNamePage
     * @param string   $parameterNameEnabled
     * @param string   $parameterNameItemsPerPage
     * @param int|null $maximumItemsPerPage
     */
    public function setConfig(bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $parameterNamePage = 'page', string $parameterNameEnabled = 'pagination', string $parameterNameItemsPerPage = 'itemsPerPage', int $maximumItemsPerPage = null)
    {
        $this->enabled = $enabled;
        $this->clientEnabled = $clientEnabled;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->maximumItemsPerPage = $maximumItemsPerPage;

        $this->parameterNamePage = $parameterNamePage;
        $this->parameterNameEnabled = $parameterNameEnabled;
        $this->parameterNameItemsPerPage = $parameterNameItemsPerPage;
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     */
    public function setResourceAndOperation(string $resourceClass, string $operationName = null)
    {
        $this->resourceClass = $resourceClass;
        $this->operationName = $operationName;
    }

    /**
     * @throws Exception
     *
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new Exception('No valid request available');
        }

        return $request;
    }

    /**
     * @throws Exception
     *
     * @return ResourceMetadata
     */
    protected function getResourceMetadata(): ResourceMetadata
    {
        if (!$this->resourceMetadata) {
            if (!$this->resourceClass) {
                throw new Exception('resourceClass and operation not set');
            }

            $this->resourceMetadata = $this->resourceMetadataFactory->create($this->resourceClass);
        }

        return $this->resourceMetadata;
    }

    /**
     * @param string $key
     * @param null   $defaultValue
     * @param bool   $resourceFallback
     *
     * @return mixed
     */
    protected function getResourceMetadataAttribute(string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        return $this->getResourceMetadata()->getCollectionOperationAttribute($this->operationName, $key, $defaultValue, $resourceFallback);
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
            $enabled = filter_var($this->getRequest()->query->get($this->parameterNameEnabled, $enabled), FILTER_VALIDATE_BOOLEAN);
        }

        return $enabled;
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
            $itemsPerPage = (float) $this->getRequest()->query->get($this->parameterNameItemsPerPage, $itemsPerPage);

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
        $page = (int) $this->getRequest()->query->get($this->parameterNamePage, 1);

        if ($page < 1) {
            $page = 1;
        }

        return $page;
    }

    /**
     * @return bool
     */
    public function isPaginationEnabledByDefault(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isClientPaginationEnabledByDefault(): bool
    {
        return $this->clientEnabled;
    }

    /**
     * @return bool
     */
    public function isClientItemsPerPageEnabledByDefault(): bool
    {
        return $this->clientItemsPerPage;
    }

    public function getDefaultMaximumItemsPerPage()
    {
        return $this->maximumItemsPerPage;
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
}
