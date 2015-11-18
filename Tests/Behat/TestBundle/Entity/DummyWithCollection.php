<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * DummyWithCollection
 *
 * @ORM\Entity
 */
class DummyWithCollection
{
    /**
     * @var int The id.
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var ArrayCollection|Dummy[]
     *
     * @ORM\ManyToMany(targetEntity="Dummy")
     */
    public $elements;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
    }

    /**
     * @param Dummy $dummy
     */
    public function addElement(Dummy $dummy)
    {
        $this->elements[] = $dummy;
    }

    /**
     * @param Dummy $dummy
     */
    public function removeElement(Dummy $dummy)
    {
        $this->elements->removeElement($dummy);
    }

    /**
     * @return ArrayCollection|Dummy[]
     */
    public function getElements()
    {
        return $this->elements;
    }
}
