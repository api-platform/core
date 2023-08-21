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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5722;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ApiResource(
    shortName: 'EventIssue5722',
    operations: [
        new GetCollection(),
        new Get(),
    ],
)]
#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ApiProperty(readable: false, writable: false, identifier: false)]
    public $id;

    #[ApiProperty(writable: false, identifier: true)]
    #[ORM\Column(type: 'uuid', unique: true)]
    public $uuid;

    #[ORM\OneToMany(targetEntity: ItemLog::class, cascade: ['persist'], orphanRemoval: false, mappedBy: 'item')]
    public Collection $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->uuid = Uuid::uuid4();
    }
}
