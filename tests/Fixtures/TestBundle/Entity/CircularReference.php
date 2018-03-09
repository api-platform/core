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
 * Circular Reference.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={"normalization_context"={"groups"={"circular"}}})
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
