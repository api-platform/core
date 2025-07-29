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
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedResourceWithRelationRelatedEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [
        new NotExposed(
            stateOptions: new Options(entityClass: MappedResourceWithRelationRelatedEntity::class),
            normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false],
        ),
    ],
    graphQlOperations: []
)]
#[Map(target: MappedResourceWithRelationRelatedEntity::class)]
class MappedResourceWithRelationRelated
{
    #[Map(if: false)]
    public string $id;

    public string $name;
}
