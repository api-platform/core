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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"normalization_context"={"groups"={"customer_read"}}}
 * )
 * @ODM\Document
 */
class Customer
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     * @Groups({"customer_read"})
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     * @Groups({"customer_read"})
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=Address::class)
     * @Groups({"customer_read"})
     */
    public $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
