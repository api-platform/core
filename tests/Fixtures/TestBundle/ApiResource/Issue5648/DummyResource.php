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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5648;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/dummy_resource_with_custom_filter{._format}', itemUriTemplate: '/dummy_resource_with_custom_filter/{id}'),
        new Get(uriTemplate: '/dummy_resource_with_custom_filter/{id}', uriVariables: ['id' => new Link(fromClass: Dummy::class)]),
    ],
    stateOptions: new Options(entityClass: Dummy::class)
)]
#[ApiFilter(CustomFilter::class)]
class DummyResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name;

    /**
     * @var RelatedDummy[]
     */
    public array $relatedDummies;
}
