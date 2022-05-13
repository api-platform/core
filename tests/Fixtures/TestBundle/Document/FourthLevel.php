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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Fourth Level.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}/third_level/fourth_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel'), 'thirdLevel' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel::class, identifiers: [], expandedValue: 'third_level', fromProperty: 'fourthLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id/third_level/fourth_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel'), 'thirdLevel' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel::class, identifiers: [], expandedValue: 'third_level', fromProperty: 'fourthLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/third_level/fourth_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel'), 'thirdLevel' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel::class, identifiers: [], expandedValue: 'third_level', fromProperty: 'fourthLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owned_dummies/{id}/owning_dummy/related_dummies/{relatedDummies}/third_level/fourth_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwnedDummy::class, identifiers: ['id'], fromProperty: 'owningDummy'), 'owningDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owning_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel'), 'thirdLevel' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel::class, identifiers: [], expandedValue: 'third_level', fromProperty: 'fourthLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_owning_dummies/{id}/owned_dummy/related_dummies/{relatedDummies}/third_level/fourth_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy::class, identifiers: ['id'], fromProperty: 'ownedDummy'), 'ownedDummy' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy::class, identifiers: [], expandedValue: 'owned_dummy', fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy::class, identifiers: ['id'], fromProperty: 'thirdLevel'), 'thirdLevel' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel::class, identifiers: [], expandedValue: 'third_level', fromProperty: 'fourthLevel')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/third_levels/{id}/fourth_level.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\ThirdLevel::class, identifiers: ['id'], fromProperty: 'fourthLevel')], status: 200, operations: [new Get()])]
#[ODM\Document]
class FourthLevel
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[Groups(['barcelona', 'chicago'])]
    #[ODM\Field(type: 'int')]
    private ?int $level = 4;
    #[ODM\ReferenceMany(targetDocument: ThirdLevel::class, cascade: ['persist'], mappedBy: 'badFourthLevel', storeAs: 'id')]
    public $badThirdLevel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level)
    {
        $this->level = $level;
    }
}
