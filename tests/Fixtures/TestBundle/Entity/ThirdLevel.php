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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}/third_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id/third_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/third_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}/third_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}/third_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel')], status: 200, operations: [new Get()])]
#[ORM\Entity]
class ThirdLevel
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'integer')]
    #[Groups(['barcelona', 'chicago'])]
    private int $level = 3;
    #[ORM\Column(type: 'boolean')]
    private bool $test = true;
    #[ORM\ManyToOne(targetEntity: FourthLevel::class, cascade: ['persist'])]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    public $fourthLevel;
    #[ORM\ManyToOne(targetEntity: FourthLevel::class, cascade: ['persist'])]
    public $badFourthLevel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    /**
     * @param bool $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    public function getFourthLevel(): ?FourthLevel
    {
        return $this->fourthLevel;
    }

    public function setFourthLevel(FourthLevel $fourthLevel = null)
    {
        $this->fourthLevel = $fourthLevel;
    }
}
