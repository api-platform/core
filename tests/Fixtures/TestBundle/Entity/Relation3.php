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

/**
 * @ApiResource
 * @ORM\Entity
 */
class Relation3
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="Relation2", orphanRemoval=true)
     */
    private $relation2s;

    public function __construct()
    {
        $this->relation2s = new ArrayCollection();
    }

    public function getRelation2s()
    {
        return $this->relation2s;
    }

    public function addRelation2(Relation2 $relation)
    {
        $this->relation2s->add($relation);

        return $this;
    }

    public function removeRelation2(Relation2 $relation)
    {
        $this->relation2s->removeElement($relation);

        return $this;
    }
}
