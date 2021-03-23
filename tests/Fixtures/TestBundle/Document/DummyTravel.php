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

/**
 * @ApiResource(filters={"dummy_travel.property"})
 * @ODM\Document
 */
class DummyTravel
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=DummyCar::class)
     */
    public $car;

    /**
     * @ODM\Field(type="bool")
     */
    public $confirmed;

    /**
     * @ODM\ReferenceOne(targetDocument=DummyPassenger::class)
     */
    public $passenger;

    public function getId()
    {
        return $this->id;
    }
}
