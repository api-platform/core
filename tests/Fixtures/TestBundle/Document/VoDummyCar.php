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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ODM\Document
 */
#[ApiResource(normalizationContext: ['groups' => ['car_read']], denormalizationContext: ['groups' => ['car_write']])]
class VoDummyCar extends VoDummyVehicle
{
    /**
     * @var VoDummyInspection[]|Collection
     *
     * @ODM\ReferenceMany(targetDocument=VoDummyInspection::class, mappedBy="car", cascade={"persist"})
     */
    #[Groups(['car_read', 'car_write'])]
    private readonly array|\Doctrine\Common\Collections\Collection $inspections;

    public function __construct(string $make, VoDummyInsuranceCompany $insuranceCompany, array $drivers, /**
     * @ODM\Field(type="int")
     */
    #[Groups(['car_read', 'car_write'])] private readonly int $mileage, /**
     * @ODM\Field
     */
    #[Groups(['car_read', 'car_write'])] private readonly string $bodyType = 'coupe')
    {
        parent::__construct($make, $insuranceCompany, $drivers);
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
