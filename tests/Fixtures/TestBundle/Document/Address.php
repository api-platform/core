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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"normalization_context"={"groups"={"address_read"}}}
 * )
 * @ODM\Document
 */
class Address
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     * @Groups({"address_read"})
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     * @Groups({"address_read"})
     */
    public $name;

    public function getId()
    {
        return $this->id;
    }
}
