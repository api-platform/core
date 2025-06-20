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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['default']], denormalizationContext: ['groups' => ['default']])]
#[ODM\Document]
class DummyTableInheritanceRelated
{
    /**
     * @var int The id
     */
    #[Groups(['default'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var Collection Related children
     */
    #[Groups(['default'])]
    #[ODM\ReferenceMany(targetDocument: DummyTableInheritance::class, mappedBy: 'parent')]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children)
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
