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
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"colors"}},
 *          "filters"={"dummy_car_colors.search_filter"}
 *     }
 * )
 * @ORM\Entity
 */
class DummyCar
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
     * @var string Something else
     *
     * @ORM\OneToMany(targetEntity="DummyCarColor", mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     */
    private $colors;

    public function __construct()
    {
        $this->colors = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * @param string $colors
     *
     * @return static
     */
    public function setColors($colors)
    {
        $this->colors = $colors;

        return $this;
    }
}
