<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"discr_abstract_dummy"}},
 *         "denormalization_context"={"groups"={"discr_abstract_dummy"}}
 *     }
 * )
 * @ORM\Entity
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "first" = "DiscrFirstDummy",
 *     "second" = "DiscrSecondDummy"
 * })
 */
abstract class DiscrAbstractDummy
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DiscrContainerDummy
     *
     * @ORM\ManyToOne(targetEntity="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DiscrContainerDummy", inversedBy="collection")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @Groups({"discr_abstract_dummy"})
     */
    protected $parent;

    /**
     * @var string A common prop amonsgt all other subclasses
     *
     * @ORM\Column(type="string")
     * @Groups({"discr_abstract_dummy", "discr_container_dummy"})
     */
    protected $common;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return static
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return DiscrContainerDummy
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param DiscrContainerDummy $parent
     * @return static
     */
    public function setParent(DiscrContainerDummy $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommon()
    {
        return $this->common;
    }

    /**
     * @param string $common
     * @return static
     */
    public function setCommon($common)
    {
        $this->common = $common;
        return $this;
    }
}