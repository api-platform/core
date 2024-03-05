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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}/third_level{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id/third_level{._format}', uriVariables: ['id' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/third_level{._format}', uriVariables: ['id' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}/third_level{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}/third_level{._format}', uriVariables: ['id' => new Link(fromClass: RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ODM\Document]
class ThirdLevel
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Groups(['barcelona', 'chicago'])]
    #[ODM\Field(type: 'int')]
    private int $level = 3;
    #[ODM\Field(type: 'bool')]
    private bool $test = true;
    #[Groups(['barcelona', 'chicago', 'friends'])]
    #[ODM\ReferenceOne(targetDocument: FourthLevel::class, cascade: ['persist'], storeAs: 'id')]
    public ?FourthLevel $fourthLevel = null;
    #[ODM\ReferenceOne(targetDocument: FourthLevel::class, cascade: ['persist'])]
    public $badFourthLevel;
    #[ODM\ReferenceMany(mappedBy: 'thirdLevel', targetDocument: RelatedDummy::class)]
    public Collection|iterable $relatedDummies;

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    public function setTest(bool $test): void
    {
        $this->test = $test;
    }

    public function getFourthLevel(): ?FourthLevel
    {
        return $this->fourthLevel;
    }

    public function setFourthLevel(?FourthLevel $fourthLevel = null): void
    {
        $this->fourthLevel = $fourthLevel;
    }
}
