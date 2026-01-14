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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid;

use ApiPlatform\Doctrine\Orm\Filter\UlidFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ApiResource(operations: [
    new Get(),
    new GetCollection(
        parameters: [
            'id' => new QueryParameter(
                filter: new UlidFilter(),
            ),
        ]
    ),
    new Post(),
])]
#[ORM\Entity]
class SymfonyUlidDevice
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    public Ulid $id;

    #[ORM\Column(type: 'ulid')]
    public Ulid $externalId;

    public function __construct(?Ulid $id = null, ?Ulid $externalId = null)
    {
        $this->id = $id ?? new Ulid();
        $this->externalId = $externalId ?? new Ulid();
    }
}
