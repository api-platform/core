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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Person.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['people.pets']])]
#[ORM\Entity]
class Person
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;
    #[ORM\Column(type: 'string')]
    #[Groups(['people.pets'])]
    public $name;
    /**
     * @var Collection<int, PersonToPet>
     */
    #[ORM\OneToMany(targetEntity: PersonToPet::class, mappedBy: 'person')]
    #[Groups(['people.pets'])]
    public $pets;
    #[ORM\OneToMany(targetEntity: Greeting::class, mappedBy: 'sender')]
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
