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

use ApiPlatform\Doctrine\Orm\Filter\UuidFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(operations: [
    new Get(),
    new GetCollection(
        parameters: [
            'id' => new QueryParameter(
                filter: new UuidFilter(),
            ),
            'myDevice' => new QueryParameter(
                filter: new UuidFilter(),
            ),
            'myDeviceExternalIdAlias' => new QueryParameter(
                filter: new UuidFilter(),
                property: 'myDevice.externalId',
            ),
        ]
    ),
    new Post(),
])]
#[ORM\Entity]
class SymfonyUuidDeviceEndpoint
{
    #[ORM\Id]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    public Uuid $id;

    #[ORM\ManyToOne]
    public ?SymfonyUuidDevice $myDevice = null;

    public function __construct(?Uuid $id = null, ?SymfonyUuidDevice $myDevice = null)
    {
        $this->id = $id ?? Uuid::v7();
        $this->myDevice = $myDevice;
    }
}
