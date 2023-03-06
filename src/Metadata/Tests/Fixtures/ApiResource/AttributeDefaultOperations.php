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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(paginationItemsPerPage: 10, graphQlOperations: [], cacheHeaders: ['shared_max_age' => 60])]
final class AttributeDefaultOperations
{
    public function __construct(#[ApiProperty(identifier: true)] private int $identifier, private string $name)
    {
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
