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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(iri="https://schema.org/Product", attributes={"normalization_context"={"groups"={"friends"}}, "filters"={"related_dummy.mongodb.friends"}})
 * @ODM\Document
 */
class RelatedDummy extends ParentDummy
{
    /**
     * @ApiSubresource
     * @ODM\Id(strategy="INCREMENT", type="integer")
     * @Groups({"friends"})
     */
    private $id;

    /**
     * @var string A name
     *
     * @ODM\Field(type="string", nullable=true)
     * @Groups({"friends"})
     */
    public $name;

    /**
     * @ODM\Field(type="string")
     * @Groups({"barcelona", "chicago", "friends"})
     */
    protected $symfony = 'symfony';

    /**
     * @var \DateTime A dummy date
     *
     * @ODM\Field(type="date")
     * @Assert\DateTime
     * @Groups({"friends"})
     */
    public $dummyDate;

    /**
     * @ApiSubresource
     * @ODM\ReferenceOne(targetDocument=ThirdLevel::class, cascade={"persist"}, nullable=true, storeAs="id")
     * @Groups({"barcelona", "chicago", "friends"})
     */
    public $thirdLevel;

    /**
     * @ApiSubresource
     * @ODM\ReferenceMany(targetDocument=RelatedToDummyFriend::class, cascade={"persist"}, mappedBy="relatedDummy", storeAs="id")
     * @Groups({"fakemanytomany", "friends"})
     */
    public $relatedToDummyFriend;

    /**
     * @var bool A dummy bool
     *
     * @ODM\Field(type="boolean")
     * @Groups({"friends"})
     */
    public $dummyBoolean;

    /**
     * @var EmbeddableDummy
     *
     * @ODM\EmbedOne(targetDocument=EmbeddableDummy::class)
     * @Groups({"friends"})
     */
    public $embeddedDummy;

    public function __construct()
    {
        $this->relatedToDummyFriend = new ArrayCollection();
        $this->embeddedDummy = new EmbeddableDummy();
    }

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

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony)
    {
        $this->symfony = $symfony;
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    /**
     * @return bool
     */
    public function isDummyBoolean()
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean)
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    /**
     * @return ThirdLevel|null
     */
    public function getThirdLevel()
    {
        return $this->thirdLevel;
    }

    public function setThirdLevel(ThirdLevel $thirdLevel = null)
    {
        $this->thirdLevel = $thirdLevel;
    }

    /**
     * Get relatedToDummyFriend.
     *
     * @return RelatedToDummyFriend
     */
    public function getRelatedToDummyFriend()
    {
        return $this->relatedToDummyFriend;
    }

    /**
     * Set relatedToDummyFriend.
     *
     * @param RelatedToDummyFriend $relatedToDummyFriend the value to set
     */
    public function addRelatedToDummyFriend(RelatedToDummyFriend $relatedToDummyFriend)
    {
        $this->relatedToDummyFriend->add($relatedToDummyFriend);
    }

    /**
     * @return EmbeddableDummy
     */
    public function getEmbeddedDummy()
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy)
    {
        $this->embeddedDummy = $embeddedDummy;
    }
}
