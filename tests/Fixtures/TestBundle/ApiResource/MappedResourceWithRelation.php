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
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceWithRelationEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    stateOptions: new Options(entityClass: MappedResourceWithRelationEntity::class),
    normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false],
    extraProperties: [
        'standard_put' => true,
    ],
    operations: [
        new Get(),
        new Put(allowCreate: true),
    ]
)]
#[Map(target: MappedResourceWithRelationEntity::class)]
class MappedResourceWithRelation
{
    public ?string $id = null;
    #[Map(if: false)]
    public ?string $relationName = null;
    #[Map(target: 'related')]
    public ?MappedResourceWithRelationRelated $relation = null;
}
