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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(filters: ['dummy_travel.property'])]
#[ORM\Entity]
class DummyTravel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id; // @phpstan-ignore-line
    #[ORM\ManyToOne(targetEntity: DummyCar::class)]
    #[ORM\JoinColumn(name: 'car_id', referencedColumnName: 'id_id')]
    public $car;
    #[ORM\Column(type: 'boolean')]
    public $confirmed;
    #[ORM\ManyToOne(targetEntity: DummyPassenger::class)]
    #[ORM\JoinColumn(name: 'passenger_id', referencedColumnName: 'id')]
    public $passenger;

    public function getId()
    {
        return $this->id;
    }
}
