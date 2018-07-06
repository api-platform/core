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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Person.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource(attributes={"normalization_context"={"groups"={"people.pets"}}})
 * @ORM\Entity
 */
class Person
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"people.pets"})
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="PersonToPet", mappedBy="person")
     * @Groups({"people.pets"})
     *
     * @var ArrayCollection
     */
    public $pets;

    /**
     * @ApiSubresource
     * @ORM\OneToMany(targetEntity="Greeting", mappedBy="sender")
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
