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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'dummyTableInheritance' => DummyTableInheritance::class,
    'dummyTableInheritanceChild' => DummyTableInheritanceChild::class,
    'dummyTableInheritanceDifferentChild' => DummyTableInheritanceDifferentChild::class,
    'dummyTableInheritanceNotApiResourceChild' => DummyTableInheritanceNotApiResourceChild::class,
])]
class DummyTableInheritance
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['default'])]
    private ?int $id = null;
    /**
     * @var string The dummy name
     */
    #[ORM\Column]
    #[Groups(['default'])]
    private string $name;
    #[ORM\ManyToOne(targetEntity: DummyTableInheritanceRelated::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?DummyTableInheritanceRelated $parent = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?DummyTableInheritanceRelated
    {
        return $this->parent;
    }

    public function setParent(?DummyTableInheritanceRelated $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
