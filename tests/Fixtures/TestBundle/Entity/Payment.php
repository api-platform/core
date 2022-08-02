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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\Payment\VoidPaymentAction;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(operations: [new Get(), new Post(uriTemplate: '/payments/{id}/void', controller: VoidPaymentAction::class, deserialize: false), new Post(), new GetCollection()])]
#[ORM\Entity]
class Payment
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\OneToOne(targetEntity: VoidPayment::class, mappedBy: 'payment')]
    private ?VoidPayment $voidPayment = null;

    public function __construct(#[ORM\Column(type: 'decimal', precision: 6, scale: 2)] private string $amount)
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function void(): void
    {
        if (null !== $this->voidPayment) {
            return;
        }
        $this->voidPayment = new VoidPayment($this);
    }

    public function getVoidPayment(): ?VoidPayment
    {
        return $this->voidPayment;
    }
}
