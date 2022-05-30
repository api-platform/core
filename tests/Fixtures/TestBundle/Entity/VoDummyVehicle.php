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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\MappedSuperclass]
abstract class VoDummyVehicle
{
    use VoDummyIdAwareTrait;
    /**
     * @var VoDummyDriver[]|Collection
     */
    #[ORM\ManyToMany(targetEntity: 'VoDummyDriver', cascade: ['persist'])]
    #[Groups(['car_read', 'car_write'])]
    private readonly array|\Doctrine\Common\Collections\Collection $drivers;

    public function __construct(
        #[ORM\Column] #[Groups(['car_read', 'car_write'])] private readonly string $make,
        #[ORM\ManyToOne(targetEntity: 'VoDummyInsuranceCompany', cascade: ['persist'])] #[Groups(['car_read', 'car_write'])] private readonly ?VoDummyInsuranceCompany $insuranceCompany,
        array $drivers
    ) {
        $this->drivers = new ArrayCollection($drivers);
    }

    public function getMake()
    {
        return $this->make;
    }

    public function getInsuranceCompany()
    {
        return $this->insuranceCompany;
    }

    /**
     * @return VoDummyDriver[]|Collection
     */
    public function getDrivers()
    {
        return $this->drivers;
    }
}
