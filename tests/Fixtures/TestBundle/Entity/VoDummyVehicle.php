<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\MappedSuperclass
 */
abstract class VoDummyVehicle
{
    use VoDummyIdAwareTrait;

    /**
     * @var string
     *
     * @ORM\Column
     * @Groups({"write"})
     */
    private $make;

    /**
     * @var VoDummyInsuranceCompany
     *
     * @ORM\ManyToOne(targetEntity="VoDummyInsuranceCompany", cascade={"persist"})
     * @Groups({"write"})
     */
    private $insuranceCompany;

    /**
     * @var VoDummyDriver[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="VoDummyDriver", cascade={"persist"})
     * @Groups({"write"})
     */
    private $drivers;

    public function __construct(
        string $make,
        VoDummyInsuranceCompany $insuranceCompany,
        array $drivers
    ) {
        $this->make = $make;
        $this->insuranceCompany = $insuranceCompany;
        $this->drivers = new ArrayCollection($drivers);
    }

    public function getMake(): string
    {
        return $this->make;
    }

    public function getInsuranceCompany(): VoDummyInsuranceCompany
    {
        return $this->insuranceCompany;
    }

    /**
     * @return VoDummyDriver[]|Collection
     */
    public function getDrivers(): Collection
    {
        return $this->drivers;
    }
}
