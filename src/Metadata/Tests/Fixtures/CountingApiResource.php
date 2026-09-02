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

namespace ApiPlatform\Metadata\Tests\Fixtures;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;

/**
 * Counts how many times a ResourceMetadataCollection scanned this resource.
 */
final class CountingApiResource extends ApiResource
{
    public int $scanCount = 0;

    public function getOperations(): ?Operations
    {
        ++$this->scanCount;

        return parent::getOperations();
    }
}
