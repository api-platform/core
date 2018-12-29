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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ODM\Document
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
     * @ODM\Id(strategy="INCREMENT", type="integer")
     *
     * @Groups({"default"})
     */
    private $id;

    /**
     * @var ArrayCollection Related children
     *
     * @ODM\ReferenceMany(targetDocument=DummyTableInheritance::class, mappedBy="parent")
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
