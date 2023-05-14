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
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(normalizationContext: ['groups' => ['order_read']], forceEager: false)]
#[ODM\Document]
class Order
{
    #[Groups(['order_read'])]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Groups(['order_read'])]
    #[ODM\ReferenceOne(targetDocument: Customer::class)]
    public $customer;
    #[Assert\NotNull]
    #[Groups(['order_read'])]
    #[ODM\ReferenceOne(targetDocument: Customer::class)]
    public $recipient;

    public function getId(): ?int
    {
        return $this->id;
    }
}
