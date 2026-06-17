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

namespace ApiPlatform\JsonApi\Tests\Fixtures;

use ApiPlatform\Metadata\ApiResource;

/**
 * A second related resource used to exercise polymorphic (union-typed) relationships.
 */
#[ApiResource]
class OtherRelatedDummy
{
    private ?int $id = null;

    public ?string $label = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
