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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedOutputEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * Output DTO mapped from the entity: exposes only "name", not "description".
 */
#[Map(source: MappedOutputEntity::class)]
class MappedOutputDto
{
    public ?int $id = null;

    public ?string $name = null;
}
