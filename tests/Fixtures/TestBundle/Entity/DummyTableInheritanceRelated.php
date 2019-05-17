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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"default"}},
 *         "denormalization_context"={"groups"={"default"}}
 *     }
 * )
 */
class DummyTableInheritanceRelated
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"default"})
     */
    private $id;

    /**
     * @var ArrayCollection Related children
     *
     * @ORM\OneToMany(targetEntity="DummyTableInheritance", mappedBy="parent")
     * @ORM\OrderBy({"id"="ASC"})
     *
     * @Groups({"default"})
     */
    private $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren($children)
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
