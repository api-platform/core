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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\State\RelationMultipleProvider;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    mercure: true,
    operations: [
        new Post(),
        new Get(
            uriTemplate: '/dummy/{firstId}/relations/{secondId}',
            uriVariables: [
                'firstId' => new Link(
                    fromClass: Dummy::class,
                    toProperty: 'first',
                    identifiers: ['id'],
                ),
                'secondId' => new Link(
                    fromClass: Dummy::class,
                    toProperty: 'second',
                    identifiers: ['id'],
                ),
            ],
            provider: RelationMultipleProvider::class,
        ),
        new GetCollection(
            uriTemplate : '/dummy/{firstId}/relations',
            uriVariables: [
                'firstId' => new Link(
                    fromClass: Dummy::class,
                    toProperty: 'first',
                    identifiers: ['id'],
                ),
            ],
            provider: RelationMultipleProvider::class,
        ),
    ]
)]

#[ORM\Entity]
class RelationMultiple
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dummy::class)]
    public ?Dummy $first = null;

    #[ORM\ManyToOne(targetEntity: Dummy::class)]
    public ?Dummy $second = null;
}
