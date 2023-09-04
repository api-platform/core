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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['default']], denormalizationContext: ['groups' => ['default']])]
class DummyTableInheritanceRelated
{
    /**
     * @var int The id
     */
    #[Groups(['default'])]
    private ?int $id = null;
    /**
     * @var Collection<int, DummyTableInheritance> Related children
     */
    #[Groups(['default'])]
    private Collection|iterable $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChildren(): Collection|iterable
    {
        return $this->children;
    }

    public function setChildren(Collection|iterable $children)
    {
        $this->children = $children;

        return $this;
    }

    public function addChild($child)
    {
        $this->children->add($child);
        $child->setParent($this);

        return $this;
    }

    public function removeChild($child)
    {
        $this->children->remove($child);

        return $this;
    }
}
