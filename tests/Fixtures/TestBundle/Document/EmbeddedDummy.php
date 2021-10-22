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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Embedded Dummy.
 *
 * @author Jordan Samouh <jordan.samouh@gmail.com>
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
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var string|null The dummy name
     *
     * @ODM\Field(type="string")
     * @Groups({"embed"})
     */
    private $name;

    /**
     * @var \DateTime|null A dummy date
     *
     * @ODM\Field(type="date")
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var EmbeddableDummy
     *
     * @ODM\EmbedOne(targetDocument=EmbeddableDummy::class)
     * @Groups({"embed"})
     */
    public $embeddedDummy;

    /**
     * @var RelatedDummy|null A related dummy
     *
     * @ODM\ReferenceOne(targetDocument=RelatedDummy::class, storeAs="id")
     */
    public $relatedDummy;

    public static function staticMethod(): void
    {
    }

    public function __construct()
    {
        $this->embeddedDummy = new EmbeddableDummy();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmbeddedDummy(): EmbeddableDummy
    {
        return $this->embeddedDummy;
    }

    public function setEmbeddedDummy(EmbeddableDummy $embeddedDummy): void
    {
        $this->embeddedDummy = $embeddedDummy;
    }

    public function getDummyDate(): ?\DateTime
    {
        return $this->dummyDate;
    }

    public function setDummyDate(\DateTime $dummyDate): void
    {
        $this->dummyDate = $dummyDate;
    }

    public function getRelatedDummy(): ?RelatedDummy
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy): void
    {
        $this->relatedDummy = $relatedDummy;
    }
}
