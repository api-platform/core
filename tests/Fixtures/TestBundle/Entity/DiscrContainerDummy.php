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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"discr_container_dummy"}},
 *         "denormalization_context"={"groups"={"discr_container_dummy"}}
 *     }
 * )
 * @ORM\Entity
 */
class DiscrContainerDummy
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
     * @var ArrayCollection|Collection|DiscrAbstractDummy[] The collection containing discriminated entities
     *
     * @ORM\OneToMany(
     *     targetEntity="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DiscrAbstractDummy",
     *     mappedBy="parent",
     *     cascade={"persist","remove"}
     * )
     *
     * @Groups({"discr_container_dummy"})
     */
    private $collection;

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
     * @return DiscrAbstractDummy[]|ArrayCollection|Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param DiscrAbstractDummy[]|ArrayCollection|Collection $collection
     * @return static
     */
    public function setCollection($collection)
    {
        $this->collection = new ArrayCollection(
            $collection instanceof Collection
                ? $collection->toArray()
                : $collection
        );
        return $this;
    }
}