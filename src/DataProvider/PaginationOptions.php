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

namespace ApiPlatform\Core\DataProvider;

class PaginationOptions
{
    private $paginationEnabled;
    private $paginationPageParameterName;
    private $clientItemsPerPage;
    private $itemsPerPageParameterName;
    private $paginationClientEnabled;
    private $paginationClientEnabledParameterName;

    public function __construct($paginationEnabled = true, string $paginationPageParameterName = 'page', bool $clientItemsPerPage = false, string $itemsPerPageParameterName = 'itemsPerPage', bool $paginationClientEnabled = false, string $paginationClientEnabledParameterName = 'pagination')
    {
        $this->paginationEnabled = $paginationEnabled;
        $this->paginationPageParameterName = $paginationPageParameterName;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
        $this->paginationClientEnabled = $paginationClientEnabled;
        $this->paginationClientEnabledParameterName = $paginationClientEnabledParameterName;
    }

    public function isPaginationEnabled()
    {
        return $this->paginationEnabled;
    }

    public function getPaginationPageParameterName()
    {
        return $this->paginationPageParameterName;
    }

    public function getClientItemsPerPage()
    {
        return $this->clientItemsPerPage;
    }

    public function getItemsPerPageParameterName()
    {
        return $this->itemsPerPageParameterName;
    }

    public function getPaginationClientEnabled()
    {
        return $this->paginationClientEnabled;
    }

    public function getPaginationClientEnabledParameterName()
    {
        return $this->paginationClientEnabledParameterName;
    }
}
