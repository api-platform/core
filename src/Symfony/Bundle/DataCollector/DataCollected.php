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

namespace ApiPlatform\Symfony\Bundle\DataCollector;

use Symfony\Component\VarDumper\Cloner\Data;

final class DataCollected
{
    public function __construct(private readonly string $resourceClass, private readonly Data $resourceMetadataCollection, private readonly array $filters, private readonly array $counters)
    {
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getResourceMetadataCollection(): Data
    {
        return $this->resourceMetadataCollection;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getCounters(): array
    {
        return $this->counters;
    }
}
