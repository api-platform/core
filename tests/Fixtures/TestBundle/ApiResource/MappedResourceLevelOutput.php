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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MappedResourceLevelOutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceLevelOutputEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    stateOptions: new Options(entityClass: MappedResourceLevelOutputEntity::class),
    output: MappedResourceLevelOutputDto::class,
    operations: [new Get(), new Patch(), new Delete()],
)]
#[Map(target: MappedResourceLevelOutputEntity::class)]
final class MappedResourceLevelOutput
{
    #[Map(if: false)]
    public ?int $id = null;

    public ?string $name = null;

    public ?string $description = null;
}
