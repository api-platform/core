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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PHPCR;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Related Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(iri="https://schema.org/Product", attributes={"normalization_context"={"groups"={"friends"}}})
 * @PHPCRODM\Document(referenceable=true, childClasses={"EmbeddableDummy"})
 */
class RelatedDummy extends ParentDummy
{
    /**
     * @Groups({"friends"})
     * @PHPCRODM\Id
     */
    private $id;

    /**
     * @PHPCRODM\Node
     */
    public $node;

    /**
     * @PHPCRODM\ParentDocument()
     */
    public $parentDocument;

    /**
     * @var string A name
     *
     * @PHPCRODM\Field(type="string")
     * @Groups({"friends"})
     */
    public $name;

    /**
     * @PHPCRODM\Field(type="string")
     * @Groups({"barcelona", "chicago", "friends"})
     */
    protected $symfony = 'symfony';

    /**
     * @var \DateTime A dummy date
     *
     * @PHPCRODM\Field(type="date")
     * @Assert\DateTime
     * @Groups({"friends"})
     */
    public $dummyDate;

//    /**
//     * @ApiSubresource
//     * @ORM\ManyToOne(targetEntity="ThirdLevel", cascade={"persist"})
//     * @Groups({"barcelona", "chicago", "friends"})
//     */
//    public $thirdLevel;

    /**
     * @ApiSubresource
     * @PHPCRODM\ReferenceMany(targetDocument="RelatedToDummyFriend", cascade={"persist"})
     * @Groups({"fakemanytomany", "friends"})
     */
    public $relatedToDummyFriend;

    public function __construct()
    {
        $this->relatedToDummyFriend = new ArrayCollection();
    }

    /**
     * @var bool A dummy bool
     *
     * @PHPCRODM\Field(type="boolean")
     * @Groups({"friends"})
     */
    public $dummyBoolean;

    /**
     * @PHPCRODM\Child
     * @Groups({"friends"})
     */
    public $embeddedDummy;

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
     * @param RelatedToDummyFriend the value to set
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
