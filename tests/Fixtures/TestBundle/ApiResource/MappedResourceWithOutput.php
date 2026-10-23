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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MappedOutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedOutputEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    stateOptions: new Options(entityClass: MappedOutputEntity::class),
    operations: [
        new Get(),
        new Post(output: MappedOutputDto::class, itemUriTemplate: '/mapped_resource_with_outputs/{id}{._format}'),
        new Patch(output: MappedOutputDto::class),
    ],
    normalizationContext: ['hydra_prefix' => false],
)]
#[Map(target: MappedOutputEntity::class)]
class MappedResourceWithOutput
{
    #[Map(if: false)]
    public ?int $id = null;

    public ?string $name = null;

    public ?string $description = null;
}
