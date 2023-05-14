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

namespace ApiPlatform\State\Pagination;

final class PaginationOptions
{
    public function __construct(private readonly bool $paginationEnabled = true, private readonly string $paginationPageParameterName = 'page', private readonly bool $clientItemsPerPage = false, private readonly string $itemsPerPageParameterName = 'itemsPerPage', private readonly bool $paginationClientEnabled = false, private readonly string $paginationClientEnabledParameterName = 'pagination', private readonly int $itemsPerPage = 30, private readonly ?int $maximumItemsPerPage = null, private readonly bool $partialPaginationEnabled = false, private readonly bool $clientPartialPaginationEnabled = false, private readonly string $partialPaginationParameterName = 'partial')
    {
    }

    public function isPaginationEnabled(): bool
    {
        return $this->paginationEnabled;
    }

    public function getPaginationPageParameterName(): string
    {
        return $this->paginationPageParameterName;
    }

    public function getClientItemsPerPage(): bool
    {
        return $this->clientItemsPerPage;
    }

    public function getItemsPerPageParameterName(): string
    {
        return $this->itemsPerPageParameterName;
    }

    public function getPaginationClientEnabled(): bool
    {
        return $this->paginationClientEnabled;
    }

    public function isPaginationClientEnabled(): bool
    {
        return $this->paginationClientEnabled;
    }

    public function getPaginationClientEnabledParameterName(): string
    {
        return $this->paginationClientEnabledParameterName;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getMaximumItemsPerPage(): ?int
    {
        return $this->maximumItemsPerPage;
    }

    public function isPartialPaginationEnabled(): bool
    {
        return $this->partialPaginationEnabled;
    }

    public function isClientPartialPaginationEnabled(): bool
    {
        return $this->clientPartialPaginationEnabled;
    }

    public function getPartialPaginationParameterName(): string
    {
        return $this->partialPaginationParameterName;
    }
}
