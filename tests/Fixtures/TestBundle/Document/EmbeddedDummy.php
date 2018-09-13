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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Embedded Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource(
 *     attributes={"filters"={"my_dummy.mongodb.search", "my_dummy.mongodb.order", "my_dummy.mongodb.date", "my_dummy.mongodb.boolean"}},
 *     itemOperations={"get", "put", "delete", "groups"={"method"="GET", "path"="/embedded_dummies_groups/{id}", "normalization_context"={"groups"={"embed"}}}}
 * )
 * @ODM\Document
 */
class EmbeddedDummy
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @var \DateTime A dummy date
     *
     * @ODM\Field(type="date")
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var EmbeddableDummy
     *
     * @ODM\EmbedOne(targetDocument="EmbeddableDummy")
     */
    public $embeddedDummy;

    /**
     * @var RelatedDummy A related dummy
     *
     * @ODM\ReferenceOne(targetDocument="RelatedDummy", storeAs="id")
     */
    public $relatedDummy;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
        $this->embeddedDummy = new EmbeddableDummy();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy)
    {
        $this->embeddedDummy = $embeddedDummy;
    }

    /**
     * @return \DateTime
     */
    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    /**
     * @return RelatedDummy
     */
    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }
}
