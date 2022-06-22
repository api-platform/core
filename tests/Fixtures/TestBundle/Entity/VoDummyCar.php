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

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['car_read']], denormalizationContext: ['groups' => ['car_write']])]
#[ORM\Entity]
class VoDummyCar extends VoDummyVehicle
{
    /**
     * @var VoDummyInspection[]|Collection
     */
    #[ORM\OneToMany(targetEntity: VoDummyInspection::class, mappedBy: 'car', cascade: ['persist'])]
    #[Groups(['car_read', 'car_write'])]
    private array|Collection $inspections;

    public function __construct(string $make, VoDummyInsuranceCompany $insuranceCompany, array $drivers, #[ORM\Column(type: 'integer')] #[Groups(['car_read', 'car_write'])] private readonly int $mileage, #[ORM\Column] #[Groups(['car_read', 'car_write'])] private readonly string $bodyType = 'coupe')
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
