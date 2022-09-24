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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ODM\MappedSuperclass]
abstract class VoDummyVehicle
{
    use VoDummyIdAwareTrait;
    /**
     * @var Collection<VoDummyDriver>
     */
    #[Groups(['car_read', 'car_write'])]
    #[ODM\ReferenceMany(targetDocument: VoDummyDriver::class, cascade: ['persist'])]
    private Collection|iterable $drivers;

    public function __construct(
        #[Groups(['car_read', 'car_write'])] #[ODM\Field] private string $make,
        #[Groups(['car_read', 'car_write'])] #[ODM\ReferenceOne(targetDocument: VoDummyInsuranceCompany::class, cascade: ['persist'])] private VoDummyInsuranceCompany $insuranceCompany,
        array $drivers,
    ) {
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
     * @return Collection<VoDummyDriver>
     */
    public function getDrivers(): Collection|iterable
    {
        return $this->drivers;
    }
}
