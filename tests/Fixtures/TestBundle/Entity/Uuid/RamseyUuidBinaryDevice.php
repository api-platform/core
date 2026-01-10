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

use ApiPlatform\Doctrine\Orm\Filter\UuidBinaryFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ApiResource(operations: [
    new Get(),
    new GetCollection(
        parameters: [
            'id' => new QueryParameter(
                filter: new UuidBinaryFilter(),
            ),
        ]
    ),
    new Post(),
])]
#[ORM\Entity]
class RamseyUuidBinaryDevice
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true)]
    public UuidInterface $id;

    #[ORM\Column(type: 'uuid_binary')]
    public UuidInterface $externalId;

    public function __construct(?UuidInterface $id = null, ?UuidInterface $externalId = null)
    {
        $this->id = $id ?? Uuid::uuid7();
        $this->externalId = $externalId ?? Uuid::uuid7();
    }
}
