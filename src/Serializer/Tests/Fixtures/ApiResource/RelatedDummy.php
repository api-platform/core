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

namespace ApiPlatform\Serializer\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Link;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(
    graphQlOperations: [
        new Query(name: 'item_query'),
        new Mutation(name: 'update', normalizationContext: ['groups' => ['chicago', 'fakemanytomany']], denormalizationContext: ['groups' => ['friends']]),
    ],
    types: ['https://schema.org/Product'],
    normalizationContext: ['groups' => ['friends']],
    filters: ['related_dummy.friends', 'related_dummy.complex_sub_query']
)]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies')], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummies/{id}/related_dummies/{relatedDummies}{._format}', uriVariables: ['id' => new Link(fromClass: Dummy::class, identifiers: ['id'], fromProperty: 'relatedDummies'), 'relatedDummies' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
#[ApiResource(uriTemplate: '/related_dummies/{id}/id{._format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, types: ['https://schema.org/Product'], filters: ['related_dummy.friends', 'related_dummy.complex_sub_query'], normalizationContext: ['groups' => ['friends']], operations: [new Get()])]
class RelatedDummy implements \Stringable
{
    #[ApiProperty(writable: false)]
    #[Groups(['chicago', 'friends'])]
    private $id;

    /**
     * @var string|null A name
     */
    #[ApiProperty(iris: ['RelatedDummy.name'])]
    #[Groups(['friends'])]
    public $name;

    #[ApiProperty(deprecationReason: 'This property is deprecated for upgrade test')]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    protected $symfony = 'symfony';

    /**
     * @var \DateTime|null A dummy date
     */
    #[Assert\DateTime]
    #[Groups(['friends'])]
    public $dummyDate;

    /**
     * @var bool|null A dummy bool
     */
    #[Groups(['friends'])]
    public ?bool $dummyBoolean = null;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony): void
    {
        $this->symfony = $symfony;
    }

    public function setDummyDate(\DateTime $dummyDate): void
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean): void
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
