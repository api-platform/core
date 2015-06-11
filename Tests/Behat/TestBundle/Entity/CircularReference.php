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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Circular Reference.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ORM\Entity
 */
class CircularReference
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\ManyToOne(targetEntity="CircularReference", inversedBy="children")
     *
     * @Groups({"circular"})
     */
    public $parent;
    /**
     * @ORM\OneToMany(targetEntity="CircularReference", mappedBy="parent")
     *
     * @Groups({"circular"})
     */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
