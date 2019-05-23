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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"car_read"}},
 *     "denormalization_context"={"groups"={"car_write"}}
 * })
 * @ODM\Document
 */
class VoDummyCar extends VoDummyVehicle
{
    /**
     * @var int
     *
     * @ODM\Field(type="integer")
     * @Groups({"car_read", "car_write"})
     */
    private $mileage;

    /**
     * @var string
     *
     * @ODM\Field
     * @Groups({"car_read", "car_write"})
     */
    private $bodyType;

    /**
     * @var VoDummyInspection[]|Collection
     *
     * @ODM\ReferenceMany(targetDocument=VoDummyInspection::class, mappedBy="car", cascade={"persist"})
     * @Groups({"car_read", "car_write"})
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
