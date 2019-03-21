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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class DummyTravel
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DummyCar")
     * @ORM\JoinColumn(name="car_id", referencedColumnName="id")
     */
    public $car;

    /**
     * @ORM\Column(type="boolean")
     */
    public $confirmed;
    /**
     * @ORM\ManyToOne(targetEntity="DummyPassenger")
     * @ORM\JoinColumn(name="passenger_id", referencedColumnName="id")
     */
    public $passenger;

    public function getId()
    {
        return $this->id;
    }
}
