<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource
 * @ORM\Entity
 */
class VoDummyInspection
{
    use VoDummyIdAwareTrait;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @Groups({"write"})
     */
    private $accepted;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Groups({"write"})
     */
    private $performed;

//    /**
//     * @var VoDummyCar
//     *
//     * @ORM\ManyToOne(targetEntity="VoDummyCar", inversedBy="inspections")
//     * @Groups({"write"})
//     */
//    private $car;

    public function __construct(bool $accepted, DateTime $performed/**, VoDummyCar $car**/)
    {
        $this->accepted = $accepted;
        $this->performed = $performed;
//        $this->car = $car;
    }

    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    public function getPerformed(): DateTime
    {
        return $this->performed;
    }

//    public function getCar(): VoDummyCar
//    {
//        return $this->car;
//    }
}
