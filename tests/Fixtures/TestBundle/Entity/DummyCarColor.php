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
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource
 * @ORM\Entity
 */
class DummyCarColor
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var DummyCar
     *
     * @ORM\ManyToOne(targetEntity="DummyCar", inversedBy="colors")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank
     */
    private $car;

    /**
     * @var string
     *
     * @ORM\Column(nullable=false)
     * @Assert\NotBlank
     *
     * @Serializer\Groups({"colors"})
     */
    private $prop = '';

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DummyCar|null
     */
    public function getCar()
    {
        return $this->car;
    }

    /**
     * @param DummyCar $car
     *
     * @return static
     */
    public function setCar(DummyCar $car)
    {
        $this->car = $car;

        return $this;
    }

    /**
     * @return string
     */
    public function getProp()
    {
        return $this->prop;
    }

    /**
     * @param string $prop
     *
     * @return static
     */
    public function setProp($prop)
    {
        $this->prop = $prop;

        return $this;
    }
}
