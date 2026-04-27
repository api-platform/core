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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MappedPatchInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedPatchEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    stateOptions: new Options(entityClass: MappedPatchEntity::class),
    operations: [
        new Get(),
        new Post(input: MappedPatchInput::class),
        new Patch(input: MappedPatchInput::class),
    ],
    normalizationContext: ['hydra_prefix' => false],
)]
#[Map(source: MappedPatchEntity::class)]
class MappedPatchResource
{
    #[Map(if: false)]
    public ?int $id = null;

    public string $name;

    public string $description;
}
