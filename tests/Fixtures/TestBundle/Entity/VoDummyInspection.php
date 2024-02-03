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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(graphQlOperations: [], normalizationContext: ['groups' => ['inspection_read']], denormalizationContext: ['groups' => ['inspection_write']], extraProperties: ['standard_put' => false])]
#[ORM\Entity]
class VoDummyInspection
{
    use VoDummyIdAwareTrait;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['car_read', 'car_write', 'inspection_read', 'inspection_write'])]
    private \DateTime $performed;

    private $attributeWithoutConstructorEquivalent;

    public function __construct(#[ORM\Column(type: 'boolean')] #[Groups(['car_read', 'car_write', 'inspection_read', 'inspection_write'])] private bool $accepted, #[ORM\ManyToOne(targetEntity: VoDummyCar::class, inversedBy: 'inspections')] #[Groups(['inspection_read', 'inspection_write'])] private ?VoDummyCar $car, ?\DateTime $performed = null, string $parameterWhichIsNotClassAttribute = '')
    {
        $this->performed = $performed ?: new \DateTime();
        $this->attributeWithoutConstructorEquivalent = $parameterWhichIsNotClassAttribute;
    }

    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    public function getCar(): ?VoDummyCar
    {
        return $this->car;
    }

    public function getPerformed(): \DateTime
    {
        return $this->performed;
    }

    public function setPerformed(\DateTime $performed)
    {
        $this->performed = $performed;

        return $this;
    }
}
