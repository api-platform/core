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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Related To Dummy Friend represent an association table for a manytomany relation.
 *
 * @ApiResource(attributes={"normalization_context"={"groups": {"fakemanytomany"}}})
 * @ORM\Entity
 */
class RelatedToDummyFriend
{
    /**
     * @var string The dummy name
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"fakemanytomany", "friends"})
     */
    private $name;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DummyFriend")
     * @ORM\JoinColumn(name="dummyfriend_id", referencedColumnName="id", nullable=false)
     * @Groups({"fakemanytomany", "friends"})
     */
    private $dummyFriend;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="RelatedDummy", inversedBy="relatedToDummyFriend")
     * @ORM\JoinColumn(name="relateddummy_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
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
     * @param $dummyFriend the value to set
     */
    public function setDummyFriend($dummyFriend)
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
     * @param $relatedDummy the value to set
     */
    public function setRelatedDummy($relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }
}
