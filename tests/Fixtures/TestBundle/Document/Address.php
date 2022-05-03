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

/**
 * @ODM\Document
 */
#[ApiResource(normalizationContext: ['groups' => ['order_read']])]
class Address
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    #[Groups(['order_read'])]
    private ?int $id = null;
    /**
     * @ODM\Field(type="string")
     */
    #[Groups(['order_read'])]
    public $name;

    public function getId()
    {
        return $this->id;
    }
}
