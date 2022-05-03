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
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table (name="`order`")
 */
#[ApiResource(normalizationContext: ['groups' => ['order_read']], forceEager: false)]
class Order
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"order_read"})
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Customer")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"order_read"})
     */
    public $customer;
    /**
     * @ORM\ManyToOne(targetEntity="Customer")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     * @Groups({"order_read"})
     */
    public $recipient;

    public function getId()
    {
        return $this->id;
    }
}
