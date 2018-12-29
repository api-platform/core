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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource
 * @ODM\Document
 */
class DummyCarColor
{
    /**
     * @var int The entity Id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var DummyCar
     *
     * @ODM\ReferenceOne(targetDocument=DummyCar::class, inversedBy="colors", storeAs="id")
     * @Assert\NotBlank
     */
    private $car;

    /**
     * @var string
     *
     * @ODM\Field(nullable=false)
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
