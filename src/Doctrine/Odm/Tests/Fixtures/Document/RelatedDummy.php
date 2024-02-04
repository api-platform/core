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

namespace ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 */
#[ODM\Document]
class RelatedDummy extends ParentDummy implements \Stringable
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string A name
     */
    #[ODM\Field(type: 'string', nullable: true)]
    public $name;
    #[ODM\Field(type: 'string')]
    protected $symfony = 'symfony';
    /**
     * @var \DateTime A dummy date
     */
    #[ODM\Field(type: 'date', nullable: true)]
    public $dummyDate;
    #[ODM\ReferenceOne(targetDocument: ThirdLevel::class, cascade: ['persist'], nullable: true, storeAs: 'id', inversedBy: 'relatedDummies')]
    public ?ThirdLevel $thirdLevel = null;
    #[ODM\ReferenceMany(targetDocument: RelatedToDummyFriend::class, cascade: ['persist'], mappedBy: 'relatedDummy', storeAs: 'id')]
    public Collection|iterable $relatedToDummyFriend;
    #[ODM\Field(type: 'bool')]
    public ?bool $dummyBoolean = null;
    #[ODM\EmbedOne(targetDocument: EmbeddableDummy::class)]
    public ?EmbeddableDummy $embeddedDummy = null;

    public function __construct()
    {
        $this->relatedToDummyFriend = new ArrayCollection();
        $this->embeddedDummy = new EmbeddableDummy();
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

    public function getThirdLevel(): ?ThirdLevel
    {
        return $this->thirdLevel;
    }

    public function setThirdLevel(?ThirdLevel $thirdLevel = null): void
    {
        $this->thirdLevel = $thirdLevel;
    }

    /**
     * Get relatedToDummyFriend.
     */
    public function getRelatedToDummyFriend(): Collection|iterable
    {
        return $this->relatedToDummyFriend;
    }

    /**
     * Set relatedToDummyFriend.
     *
     * @param RelatedToDummyFriend $relatedToDummyFriend the value to set
     */
    public function addRelatedToDummyFriend(RelatedToDummyFriend $relatedToDummyFriend): void
    {
        $this->relatedToDummyFriend->add($relatedToDummyFriend);
    }

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy): void
    {
        $this->embeddedDummy = $embeddedDummy;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
