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

class PaginationHelperFactory
{
    private $requestStack;
    private $resourceMetadataFactory;

    private $enabled = true;
    private $clientEnabled = false;
    private $clientItemsPerPage = false;
    private $itemsPerPage = 30;
    private $maximumItemsPerPage = null;

    private $parameterNamePage = 'page';
    private $parameterNameEnabled = 'pagination';
    private $parameterNameItemsPerPage = 'itemsPerPage';

    public function __construct(RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory, bool $enabled = true, bool $clientEnabled = false, bool $clientItemsPerPage = false, int $itemsPerPage = 30, string $parameterNamePage = 'page', string $parameterNameEnabled = 'pagination', string $parameterNameItemsPerPage = 'itemsPerPage', int $maximumItemsPerPage = null)
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
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     *
     * @return PaginationHelper
     */
    public function create(string $resourceClass, string $operationName = null): PaginationHelper
    {
        return new PaginationHelper($this->requestStack, $this->resourceMetadataFactory, $resourceClass, $operationName, $this->enabled, $this->clientEnabled, $this->clientItemsPerPage, $this->itemsPerPage, $this->parameterNamePage, $this->parameterNameEnabled, $this->parameterNameItemsPerPage, $this->maximumItemsPerPage);
    }
}
