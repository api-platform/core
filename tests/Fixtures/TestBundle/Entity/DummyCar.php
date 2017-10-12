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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"colors"}}
 *     }
 * )
 * @ORM\Entity
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(PropertyFilter::class, arguments={"parameterName": "foobar"})
 * @ApiFilter(GroupFilter::class, arguments={"parameterName": "foobargroups"})
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
     * @var mixed Something else
     *
     * @ORM\OneToMany(targetEntity="DummyCarColor", mappedBy="car")
     *
     * @Serializer\Groups({"colors"})
     * @ApiFilter(SearchFilter::class, properties={"colors.prop": "ipartial"})
     */
    private $colors;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @ApiFilter(SearchFilter::class, strategy="partial")
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $canSell;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $availableAt;

    public function __construct()
    {
        $this->colors = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * @param mixed $colors
     *
     * @return static
     */
    public function setColors($colors)
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string name the value to set
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get canSell.
     *
     * @return bool canSell
     */
    public function getCanSell(): bool
    {
        return $this->canSell;
    }

    /**
     * Set canSell.
     *
     * @param bool canSell the value to set
     */
    public function setCanSell(bool $canSell)
    {
        $this->canSell = $canSell;
    }

    /**
     * Get availableAt.
     *
     * @return \DateTime availableAt
     */
    public function getAvailableAt(): \DateTime
    {
        return $this->availableAt;
    }

    /**
     * Set availableAt.
     *
     * @param \DateTime availableAt the value to set
     */
    public function setAvailableAt(\DateTime $availableAt)
    {
        $this->availableAt = $availableAt;
    }
}
