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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedPatchEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * Input DTO for PATCH — maps directly to entity.
 * Uses uninitialized properties so ObjectMapper skips unsent fields.
 */
#[Map(target: MappedPatchEntity::class)]
class MappedPatchInput
{
    public string $name;

    public string $description;
}
