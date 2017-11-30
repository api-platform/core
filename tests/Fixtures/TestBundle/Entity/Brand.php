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
class Brand
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar", inversedBy="brand")
     * @ORM\JoinTable(
     *     name="CarToBrand",
     *     joinColumns={@ORM\JoinColumn(name="brand_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@ORM\JoinColumn(name="car_id", referencedColumnName="id", nullable=false)}
     * )
     */
    private $car;

    public function __construct()
    {
        $this->car = new ArrayCollection();
    }

    public function addCar(DummyCar $car)
    {
        $this->car[] = $car;
    }

    public function removeCar(DummyCar $car)
    {
        $this->car->removeElement($car);
    }

    public function getCar()
    {
        return $this->car->getValues();
    }

    public function getId()
    {
        return $this->id;
    }
}
