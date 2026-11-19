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
use ApiPlatform\Metadata\Get;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(operations: [new Get()])]
#[ORM\Entity]
class Supplier
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';

    /** @var Collection<int, DeliveryDay> */
    #[ORM\OneToMany(targetEntity: DeliveryDay::class, mappedBy: 'supplier')]
    public Collection $deliveryDays;

    public function __construct()
    {
        $this->deliveryDays = new ArrayCollection();
    }
}
