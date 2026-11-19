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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlMappedSelfReference;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [],
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
    ]
)]
#[ORM\Entity]
class MappedSelfReference
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public ?string $name = null;

    // A real Doctrine association pointing back to the same resource class. Its
    // link has toClass === resourceClass, so the GraphQL root-item filter must
    // not mistake it for the identifier-self link and emit a bogus self-join.
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    public ?self $parent = null;
}
