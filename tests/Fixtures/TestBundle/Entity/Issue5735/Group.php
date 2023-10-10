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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5735;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations : [new Get(), new GetCollection()],
    routePrefix: '/issue5735'
)]
#[ORM\Entity]
#[ORM\Table(name: 'issue5735_group')]
class Group
{
    #[ApiProperty(readable: false, writable: false, identifier: false)]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ApiProperty(writable: false, identifier: true)]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToMany(targetEntity: Issue5735User::class, inversedBy: 'groups')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(Issue5735User $user): void
    {
        $this->users->add($user);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
