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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\NonResourceClass;

#[ApiResource]
final class ResourceWithIterableUnionProperty
{
    /**
     * @param array<int, Species|NonResourceClass|string|int> $unionItems
     */
    public function __construct(
        public int $id,
        public array $unionItems = [],
    ) {
    }
}
