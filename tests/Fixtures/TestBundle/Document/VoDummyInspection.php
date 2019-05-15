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
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"inspection_read"}},
 *     "denormalization_context"={"groups"={"inspection_write"}}
 * })
 * @ODM\Document
 */
class VoDummyInspection
{
    use VoDummyIdAwareTrait;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
     * @Groups({"car_read", "car_write", "inspection_read", "inspection_write"})
     */
    private $accepted;

    /**
     * @var VoDummyCar
     *
     * @ODM\ReferenceOne(targetDocument=VoDummyCar::class, inversedBy="inspections")
     * @Groups({"inspection_read", "inspection_write"})
     */
    private $car;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     * @Groups({"car_read", "car_write", "inspection_read", "inspection_write"})
     */
    private $performed;

    private $attributeWithoutConstructorEquivalent;

    public function __construct(bool $accepted, VoDummyCar $car, DateTime $performed = null, string $parameterWhichIsNotClassAttribute = '')
    {
        $this->accepted = $accepted;
        $this->car = $car;
        $this->performed = $performed ?: new DateTime();
        $this->attributeWithoutConstructorEquivalent = $parameterWhichIsNotClassAttribute;
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
