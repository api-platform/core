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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: '/issue5735'
)]
#[ApiFilter(SearchFilter::class, properties: ['groups' => 'exact'])]
#[ORM\Entity]
#[ORM\Table(name: 'issue5735_user')]
class Issue5735User
{
    #[ApiProperty(readable: false, writable: false, identifier: false)]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ApiProperty(writable: false, identifier: true)]
    #[ORM\Column(type: 'symfony_uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'users')]
    private Collection $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->uuid = Uuid::v4();
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid($uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): void
    {
        $this->groups->add($group);
        if (!$group->getUsers()->contains($this)) {
            $group->addUser($this);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }
}
