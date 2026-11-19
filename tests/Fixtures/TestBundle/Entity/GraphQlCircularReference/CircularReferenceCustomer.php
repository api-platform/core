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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\GraphQlCircularReference;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
class CircularReferenceCustomer
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public ?string $name = null;

    #[ORM\OneToMany(targetEntity: CircularReferenceAddress::class, mappedBy: 'owner', cascade: ['persist'])]
    public Collection $addresses;

    #[ORM\ManyToOne(targetEntity: CircularReferenceAddress::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?CircularReferenceAddress $invoiceAddress = null;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }
}
