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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\Payment\VoidPaymentAction;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 *
 * @ApiResource(
 *     itemOperations={
 *         "get",
 *         "post_void"={
 *             "method"="POST",
 *             "path"="/payments/{id}/void",
 *             "controller"=VoidPaymentAction::class,
 *             "deserialize"=false,
 *         },
 *     },
 * )
 */
class Payment
{
    /**
     * @var int|null
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string
     */
    private $amount;

    /**
     * @ODM\ReferenceOne(targetDocument=VoidPayment::class, mappedBy="payment")
     */
    private $voidPayment;

    public function __construct(string $amount)
    {
        $this->amount = $amount;
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
