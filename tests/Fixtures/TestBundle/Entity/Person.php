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
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\AcademicGrade;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;
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
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: GenderTypeEnum::class)]
    #[Groups(['people.pets'])]
    public ?GenderTypeEnum $genderType = GenderTypeEnum::MALE;

    #[ORM\Column(type: 'string')]
    #[Groups(['people.pets'])]
    public string $name;

    /** @var array<AcademicGrade> */
    #[ORM\Column(nullable: true)]
    #[Groups(['people.pets'])]
    public array $academicGrades = [];

    #[ORM\OneToMany(targetEntity: PersonToPet::class, mappedBy: 'person')]
    #[Groups(['people.pets'])]
    public Collection|iterable $pets;

    #[ORM\OneToMany(targetEntity: Greeting::class, mappedBy: 'sender')]
    public Collection|iterable|null $sentGreetings = null;

    public function __construct()
    {
        $this->pets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
