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
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(graphQlOperations: [], normalizationContext: ['groups' => ['inspection_read']], denormalizationContext: ['groups' => ['inspection_write']])]
#[ODM\Document]
class VoDummyInspection
{
    use VoDummyIdAwareTrait;
    #[Groups(['car_read', 'car_write', 'inspection_read', 'inspection_write'])]
    #[ODM\Field(type: 'date')]
    private \DateTime $performed;

    public function __construct(#[Groups(['car_read', 'car_write', 'inspection_read', 'inspection_write'])] #[ODM\Field(type: 'bool')] private readonly bool $accepted, #[Groups(['inspection_read', 'inspection_write'])] #[ODM\ReferenceOne(targetDocument: VoDummyCar::class, inversedBy: 'inspections')] private readonly VoDummyCar $car, DateTime $performed = null, private readonly string $attributeWithoutConstructorEquivalent = '')
    {
        $this->performed = $performed ?: new DateTime();
    }

    public function isAccepted()
    {
        return $this->accepted;
    }

    public function getCar()
    {
        return $this->car;
    }

    public function getPerformed()
    {
        return $this->performed;
    }

    public function setPerformed(DateTime $performed)
    {
        $this->performed = $performed;

        return $this;
    }
}
