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
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Person.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource(attributes={"normalization_context"={"groups"={"people.pets"}}})
 * @ODM\Document
 */
class Person
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     * @Groups({"people.pets"})
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=PersonToPet::class, mappedBy="person")
     * @Groups({"people.pets"})
     *
     * @var ArrayCollection
     */
    public $pets;

    /**
     * @ApiSubresource
     * @ODM\ReferenceMany(targetDocument=Greeting::class, mappedBy="sender")
     */
    public $sentGreetings;

    public function __construct()
    {
        $this->pets = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
