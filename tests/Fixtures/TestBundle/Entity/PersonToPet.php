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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * PersonToPet.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ORM\Entity]
class PersonToPet
{
    /**
     * @var Pet
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id')]
    #[Groups(['people.pets'])]
    public $pet;
    /**
     * @var Person
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id')]
    public $person;
}
