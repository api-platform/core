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
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedDummy as RelatedDummyModel;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(dataModel=RelatedDummyModel::class, iri="https://schema.org/Product", attributes={"normalization_context"={"groups"={"friends"}}, "filters"={"related_dummy.friends", "related_dummy.complex_sub_query"}})
 */
class RelatedDummy extends ParentDummy
{
    /**
     * @ApiProperty(identifier=true, writable=false)
     * @Groups({"chicago", "friends"})
     */
    private $id;

    /**
     * @var ?string A name
     *
     * @Groups({"friends"})
     */
    public $name;

    /**
     * @Groups({"barcelona", "chicago", "friends"})
     */
    protected $symfony = 'symfony';

    /**
     * @var ?\DateTime A dummy date
     *
     * @Assert\DateTime
     * @Groups({"friends"})
     */
    public $dummyDate;

    /**
     * @ApiSubresource
     * @Groups({"barcelona", "chicago", "friends"})
     */
    public $thirdLevel;

    /**
     * @ApiSubresource
     * @Groups({"fakemanytomany", "friends"})
     */
    public $relatedToDummyFriend;

    /**
     * @var ?bool A dummy bool
     *
     * @Groups({"friends"})
     */
    public $dummyBoolean;

    /**
     * @var array
     *
     * @Groups({"friends"})
     */
    public $embeddedDummy;

    public function __construct()
    {
        $this->relatedToDummyFriend = [];
        $this->embeddedDummy = [];
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

    public function setDummyDate(?\DateTime $dummyDate)
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
     * @return RelatedToDummyFriend[]
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

    public function getEmbeddedDummy()
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy($embeddedDummy)
    {
        $this->embeddedDummy = $embeddedDummy;
    }
}
