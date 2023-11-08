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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(graphql={"item_query", "collection_query", "create", "update"={"normalization_context"={"groups"={"chicago", "fakemanytomany"}}, "denormalization_context"={"groups"={"friends"}}}}, iri="https://schema.org/Product", attributes={"normalization_context"={"groups"={"friends"}}, "filters"={"related_dummy.friends", "related_dummy.complex_sub_query"}}, mercure=true)
 *
 * @ORM\Entity
 *
 * @ApiFilter(SearchFilter::class, properties={"id"})
 */
class RelatedDummy extends ParentDummy
{
    /**
     * @ApiProperty(writable=false)
     *
     * @ApiSubresource
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"chicago", "friends"})
     */
    private $id;

    /**
     * @var string|null A name
     *
     * @ApiProperty(iri="RelatedDummy.name")
     *
     * @ORM\Column(nullable=true)
     *
     * @Groups({"friends"})
     */
    public $name;

    /**
     * @ApiProperty(attributes={"deprecation_reason"="This property is deprecated for upgrade test"})
     *
     * @ORM\Column
     *
     * @Groups({"barcelona", "chicago", "friends"})
     *
     * @ApiFilter(SearchFilter::class)
     * @ApiFilter(ExistsFilter::class)
     */
    protected $symfony = 'symfony';

    /**
     * @var \DateTime|null A dummy date
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\DateTime
     *
     * @Groups({"friends"})
     *
     * @ApiFilter(DateFilter::class)
     */
    public $dummyDate;

    /**
     * @ApiSubresource
     *
     * @ORM\ManyToOne(targetEntity="ThirdLevel", cascade={"persist"})
     *
     * @Groups({"barcelona", "chicago", "friends"})
     */
    public $thirdLevel;

    /**
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="RelatedToDummyFriend", cascade={"persist"}, mappedBy="relatedDummy")
     *
     * @Groups({"fakemanytomany", "friends"})
     */
    public $relatedToDummyFriend;

    /**
     * @var bool|null A dummy bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"friends"})
     */
    public $dummyBoolean;

    /**
     * @var EmbeddableDummy
     *
     * @ORM\Embedded(class="EmbeddableDummy")
     *
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

    public function isDummyBoolean(): ?bool
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

    public function getThirdLevel(): ?ThirdLevel
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
     * @return Collection<RelatedToDummyFriend>
     */
    public function getRelatedToDummyFriend(): Collection
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

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy)
    {
        $this->embeddedDummy = $embeddedDummy;
    }
}
