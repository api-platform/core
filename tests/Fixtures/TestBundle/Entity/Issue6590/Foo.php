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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6590;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'foo6590')]
class Foo
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\OneToMany(targetEntity: Bar::class, mappedBy: 'foo')]
    private Collection $bars;

    public function __construct()
    {
        $this->bars = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<Bar>
     */
    public function getBars(): Collection
    {
        return $this->bars;
    }

    /**
     * @param Collection<Bar> $bars
     */
    public function setBars(Collection $bars): self
    {
        $this->bars = $bars;

        return $this;
    }
}
