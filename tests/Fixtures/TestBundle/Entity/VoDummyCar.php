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

    /**
     * @var VoDummyInspection[]|Collection
     *
     * @ORM\OneToMany(targetEntity="VoDummyInspection", mappedBy="car", cascade={"persist"})
     * @Groups({"write"})
     */
    private $inspections;

    public function __construct(
        string $make,
        VoDummyInsuranceCompany $insuranceCompany,
        array $drivers,
        int $mileage,
        string $bodyType = 'coupe'
    ) {
        parent::__construct($make, $insuranceCompany, $drivers);
        $this->mileage = $mileage;
        $this->bodyType = $bodyType;
        $this->inspections = new ArrayCollection();
    }

    public function getMileage()
    {
        return $this->mileage;
    }

    public function getBodyType()
    {
        return $this->bodyType;
    }

    public function getInspections()
    {
        return $this->inspections;
    }
}
