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

#[ApiResource]
#[ORM\Entity]
class VoidPayment
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    public function __construct(#[ORM\OneToOne(targetEntity: Payment::class, inversedBy: 'voidPayment')] #[ORM\JoinColumn(nullable: false)] private Payment $payment)
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
