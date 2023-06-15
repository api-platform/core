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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyWithSubEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5605\MainResourceProvider;

#[ApiResource(
    operations  : [
        new Get(uriTemplate: '/dummy_with_subresource/{id}', uriVariables: ['id']),
        new GetCollection(uriTemplate: '/dummy_with_subresource'),
    ],
    provider    : MainResourceProvider::class,
    stateOptions: new Options(entityClass: DummyWithSubEntity::class)
)]
#[ApiFilter(SearchFilter::class, properties: ['subEntity'])]
class MainResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
    public string $name;
    public SubResource $subResource;
}
