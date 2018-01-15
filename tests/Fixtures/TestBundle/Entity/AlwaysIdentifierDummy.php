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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * AlwaysIdentifierDummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"alwaysIdentifierGroup"}}
 * })
 * @ORM\Entity
 */
class AlwaysIdentifierDummy
{
    /**
     * @var int id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection children dummies
     *
     * @ORM\ManyToMany(targetEntity="AlwaysIdentifierDummy")
     * @ApiProperty(alwaysIdentifier=true)
     * @Groups({"alwaysIdentifierGroup"})
     */
    private $children;

    /**
     * @var ArrayCollection related dummies
     *
     * @ORM\ManyToMany(targetEntity="AlwaysIdentifierDummy")
     * @Groups({"alwaysIdentifierGroup"})
     * @ORM\JoinTable(name="alwaysidentifierdummies_related")
     */
    private $related;

    /**
     * @var AlwaysIdentifierDummy parent dummy
     *
     * @ORM\ManyToOne(targetEntity="AlwaysIdentifierDummy")
     * @ApiProperty(alwaysIdentifier=true)
     * @Groups({"alwaysIdentifierGroup"})
     */
    private $parent;

    public function __construct()
    {
        $this->related = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param ArrayCollection $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * @return ArrayCollection
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * @param ArrayCollection $related
     */
    public function setRelated($related)
    {
        $this->related = $related;
    }

    /**
     * @return AlwaysIdentifierDummy
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param AlwaysIdentifierDummy $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}
