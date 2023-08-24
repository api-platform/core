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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5605;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummySubEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5605\SubResourceProvider;

#[ApiResource(
    operations  : [
        new Get(
            uriTemplate: '/dummy_subresource/{strId}',
            uriVariables: ['strId']
        ),
    ],
    provider: SubResourceProvider::class,
    stateOptions: new Options(entityClass: DummySubEntity::class)
)]
class SubResource
{
    #[ApiProperty(identifier: true)]
    public string $strId;

    public string $name;
}
