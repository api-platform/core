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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\MongoDB;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related To Dummy Friend represent an association table for a manytomany relation.
 *
 * @ApiResource(attributes={"normalization_context"={"groups"={"fakemanytomany"}}, "filters"={"related_to_dummy_friend.name"}})
 * @ODM\Document
 */
class RelatedToDummyFriend
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"fakemanytomany", "friends"})
     */
    private $name;

    /**
     * @var string|null The dummy description
     *
     * @ODM\Field(type="string")
     * @Groups({"fakemanytomany", "friends"})
     */
    private $description;

    /**
     * @ODM\Id
     * @ODM\ReferenceOne(targetDocument="DummyFriend")
     * @Groups({"fakemanytomany", "friends"})
     * @Assert\NotNull
     */
    private $dummyFriend;

    /**
     * @ODM\Id
     * @ODM\ReferenceOne(targetDocument="RelatedDummy", inversedBy="relatedToDummyFriend")
     * @Assert\NotNull
     */
    private $relatedDummy;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Gets dummyFriend.
     *
     * @return DummyFriend
     */
    public function getDummyFriend()
    {
        return $this->dummyFriend;
    }

    /**
     * Sets dummyFriend.
     *
     * @param DummyFriend $dummyFriend the value to set
     */
    public function setDummyFriend(DummyFriend $dummyFriend)
    {
        $this->dummyFriend = $dummyFriend;
    }

    /**
     * Gets relatedDummy.
     *
     * @return RelatedDummy
     */
    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    /**
     * Sets relatedDummy.
     *
     * @param RelatedDummy $relatedDummy the value to set
     */
    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }
}
