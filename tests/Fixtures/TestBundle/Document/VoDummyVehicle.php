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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ODM\MappedSuperclass
 */
abstract class VoDummyVehicle
{
    use VoDummyIdAwareTrait;

    /**
     * @var string
     *
     * @ODM\Field
     * @Groups({"car_read", "car_write"})
     */
    private $make;

    /**
     * @var VoDummyInsuranceCompany
     *
     * @ODM\ReferenceOne(targetDocument=VoDummyInsuranceCompany::class, cascade={"persist"})
     * @Groups({"car_read", "car_write"})
     */
    private $insuranceCompany;

    /**
     * @var VoDummyDriver[]|Collection
     *
     * @ODM\ReferenceMany(targetDocument=VoDummyDriver::class, cascade={"persist"})
     * @Groups({"car_read", "car_write"})
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
