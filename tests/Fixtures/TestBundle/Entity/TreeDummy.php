<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(graphQlOperations: [new Query(), new QueryCollection()])]
#[ORM\Entity]
class TreeDummy
{
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TreeDummy::class, inversedBy: 'children')]
    public ?TreeDummy $parent = null;

    /** @var Collection<int, TreeDummy> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: TreeDummy::class)]
    public Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?TreeDummy
    {
        return $this->parent;
    }

    public function setParent(?TreeDummy $parent): void
    {
        $this->parent = $parent;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }
}
