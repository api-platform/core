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
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ApiResource(operations: [
    new Get(),
    new GetCollection(
        parameters: [
            'id' => new QueryParameter(
                filter: new UlidFilter(),
            ),
            'myDevice' => new QueryParameter(
                filter: new UlidFilter(),
            ),
            'myDeviceExternalIdAlias' => new QueryParameter(
                filter: new UlidFilter(),
                property: 'myDevice.externalId',
            ),
        ]
    ),
    new Post(),
])]
#[ORM\Entity]
class SymfonyUlidDeviceEndpoint
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    public Ulid $id;

    #[ORM\ManyToOne]
    public ?SymfonyUlidDevice $myDevice = null;

    public function __construct(?Ulid $id = null, ?SymfonyUlidDevice $myDevice = null)
    {
        $this->id = $id ?? new Ulid();
        $this->myDevice = $myDevice;
    }
}
