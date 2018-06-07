<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"read", "write"}},
 *     "denormalization_context"={"groups"={"write"}}
 * })
 * @ORM\Entity
 */
class VoDummyCar extends VoDummyVehicle
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Groups({"write"})
     */
    private $mileage;

    /**
     * @var string
     *
     * @ORM\Column
     * @Groups({"write"})
     */
    private $bodyType;

//    /**
//     * @var VoDummyInspection[]|Collection
//     *
//     * @ORM\OneToMany(targetEntity="VoDummyInspection", mappedBy="car", cascade={"persist"})
//     * @Groups({"write"})
//     */
//    private $inspections;

    public function __construct(
        int $mileage,
//        array $inspections,
        string $make,
        VoDummyInsuranceCompany $insuranceCompany,
        array $drivers,
        string $bodyType = 'coupe'
    ) {
        parent::__construct($make, $insuranceCompany, $drivers);
        $this->mileage = $mileage;
//        $this->inspections = new ArrayCollection($inspections);
        $this->bodyType = $bodyType;
    }

    public function getMileage(): int
    {
        return $this->mileage;
    }

    public function getBodyType(): string
    {
        return $this->bodyType;
    }

//    public function getInspections(): Collection
//    {
//        return $this->inspections;
//    }
}
