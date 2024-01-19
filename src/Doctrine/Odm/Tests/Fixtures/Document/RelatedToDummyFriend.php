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

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Related To Dummy Friend represent an association table for a manytomany relation.
 */
#[ODM\Document]
class RelatedToDummyFriend
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    /**
     * @var string The dummy name
     */
    #[ODM\Field(type: 'string')]
    private $name;
    /**
     * @var string|null The dummy description
     */
    #[ODM\Field(type: 'string')]
    private ?string $description = null;
    #[ODM\ReferenceOne(targetDocument: DummyFriend::class, storeAs: 'id')]
    private DummyFriend $dummyFriend;
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class, inversedBy: 'relatedToDummyFriend', storeAs: 'id')]
    private RelatedDummy $relatedDummy;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * Gets dummyFriend.
     */
    public function getDummyFriend(): DummyFriend
    {
        return $this->dummyFriend;
    }

    /**
     * Sets dummyFriend.
     *
     * @param DummyFriend $dummyFriend the value to set
     */
    public function setDummyFriend(DummyFriend $dummyFriend): void
    {
        $this->dummyFriend = $dummyFriend;
    }

    /**
     * Gets relatedDummy.
     */
    public function getRelatedDummy(): RelatedDummy
    {
        return $this->relatedDummy;
    }

    /**
     * Sets relatedDummy.
     *
     * @param RelatedDummy $relatedDummy the value to set
     */
    public function setRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummy = $relatedDummy;
    }
}
