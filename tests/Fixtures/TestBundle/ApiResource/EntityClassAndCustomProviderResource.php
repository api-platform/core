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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SeparatedEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\State\EntityClassAndCustomProviderResourceProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/entityClassAndCustomProviderResources/{id}',
        ),
        new GetCollection(
            uriTemplate: '/entityClassAndCustomProviderResources'
        ),
    ],
    provider: EntityClassAndCustomProviderResourceProvider::class,
    stateOptions: new Options(entityClass: SeparatedEntity::class)
)]
class EntityClassAndCustomProviderResource
{
    public ?int $id;
    public ?string $value;
}
