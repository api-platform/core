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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity]
class DummyCarColor
{
    /**
     * @var int The entity Id
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: DummyCar::class, inversedBy: 'colors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', referencedColumnName: 'id_id')]
    #[Assert\NotBlank]
    private DummyCar $car;
    #[ApiFilter(SearchFilter::class)]
    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank]
    #[Serializer\Groups(['colors'])]
    private string $prop = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?DummyCar
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

    public function getProp(): string
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
