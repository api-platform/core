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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedToDummyFriend as RelatedToDummyFriendModel;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related To Dummy Friend represent an association table for a manytomany relation.
 *
 * @ApiResource(dataModel=RelatedToDummyFriendModel::class, attributes={"normalization_context"={"groups"={"fakemanytomany"}}, "filters"={"related_to_dummy_friend.name"}})
 */
class RelatedToDummyFriend
{
    /**
     * @var string The dummy name
     *
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"fakemanytomany", "friends"})
     */
    private $name;

    /**
     * @var string|null The dummy description
     *
     * @Groups({"fakemanytomany", "friends"})
     */
    private $description;

    /**
     * @ApiProperty(identifier=true)
     * @Groups({"fakemanytomany", "friends"})
     * @Assert\NotNull
     */
    private $dummyFriend;

    /**
     * @ApiProperty(identifier=true)
     * @Assert\NotNull
     */
    private $relatedDummy;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
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
