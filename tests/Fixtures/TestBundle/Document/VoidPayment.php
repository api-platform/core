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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[ODM\Document]
class VoidPayment
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    public function __construct(
        #[ODM\ReferenceOne(targetDocument: Payment::class, inversedBy: 'voidPayment')] private Payment $payment,
    ) {
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
