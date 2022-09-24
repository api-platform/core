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
     * @var Collection<VoDummyDriver>
     */
    #[ORM\ManyToMany(targetEntity: VoDummyDriver::class, cascade: ['persist'])]
    #[Groups(['car_read', 'car_write'])]
    private Collection|iterable $drivers;

    public function __construct(
        #[ORM\Column] #[Groups(['car_read', 'car_write'])] private string $make,
        #[ORM\ManyToOne(targetEntity: VoDummyInsuranceCompany::class, cascade: ['persist'])] #[Groups(['car_read', 'car_write'])] private ?VoDummyInsuranceCompany $insuranceCompany,
        array $drivers,
    ) {
        $this->drivers = new ArrayCollection($drivers);
    }

    public function getMake(): string
    {
        return $this->make;
    }

    public function getInsuranceCompany(): ?VoDummyInsuranceCompany
    {
        return $this->insuranceCompany;
    }

    /**
     * @return Collection<VoDummyDriver>
     */
    public function getDrivers(): Collection|iterable
    {
        return $this->drivers;
    }
}
