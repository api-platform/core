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
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\AcademicGrade;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Person.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource(normalizationContext: ['groups' => ['people.pets']])]
#[ODM\Document]
class Person
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string', enumType: GenderTypeEnum::class, nullable: true)]
    #[Groups(['people.pets'])]
    public ?GenderTypeEnum $genderType = GenderTypeEnum::MALE;

    #[Groups(['people.pets'])]
    #[ODM\Field(type: 'string')]
    public string $name;

    /** @var array<AcademicGrade> */
    #[ODM\Field(nullable: true)]
    #[Groups(['people.pets'])]
    public array $academicGrades = [];

    #[Groups(['people.pets'])]
    #[ODM\ReferenceMany(targetDocument: PersonToPet::class, mappedBy: 'person')]
    public Collection|iterable $pets;

    #[ODM\ReferenceMany(targetDocument: Greeting::class, mappedBy: 'sender')]
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
