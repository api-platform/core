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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\Payment\VoidPaymentAction;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(operations: [new Get(), new Post(uriTemplate: '/payments/{id}/void', controller: VoidPaymentAction::class, deserialize: false), new Post(), new GetCollection()])]
#[ODM\Document]
class Payment
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ODM\ReferenceOne(targetDocument: VoidPayment::class, mappedBy: 'payment')]
    private ?VoidPayment $voidPayment = null;

    public function __construct(private ?string $amount)
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
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
