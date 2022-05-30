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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource]
class Relation2
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    public $id;
    /**
     * @ORM\OneToMany(targetEntity="Relation1", mappedBy="relation2")
     */
    public $relation1s;

    public function __construct()
    {
        $this->relation1s = new ArrayCollection();
    }
}
