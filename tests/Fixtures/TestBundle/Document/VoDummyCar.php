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

#[ApiResource(normalizationContext: ['groups' => ['car_read']], denormalizationContext: ['groups' => ['car_write']])]
#[ODM\Document]
class VoDummyCar extends VoDummyVehicle
{
    #[Groups(['car_read', 'car_write'])]
    #[ODM\ReferenceMany(targetDocument: VoDummyInspection::class, mappedBy: 'car', cascade: ['persist'])]
    private Collection|iterable $inspections;

    public function __construct(string $make, VoDummyInsuranceCompany $insuranceCompany, array $drivers, #[Groups(['car_read', 'car_write'])] #[ODM\Field(type: 'int')] private int $mileage, #[Groups(['car_read', 'car_write'])] #[ODM\Field] private string $bodyType = 'coupe')
    {
        parent::__construct($make, $insuranceCompany, $drivers);
        $this->inspections = new ArrayCollection();
    }

    public function getMileage(): int
    {
        return $this->mileage;
    }

    public function getBodyType(): string
    {
        return $this->bodyType;
    }

    public function getInspections(): Collection|iterable
    {
        return $this->inspections;
    }
}
