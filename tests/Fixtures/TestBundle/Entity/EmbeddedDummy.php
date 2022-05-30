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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Embedded Dummy.
 *
 * @author Jordan Samouh <jordan.samouh@gmail.com>
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(), new Put(), new Delete(), new Get(uriTemplate: '/embedded_dummies_groups/{id}', normalizationContext: ['groups' => ['embed']]), new Post(), new GetCollection()], filters: ['my_dummy.search', 'my_dummy.order', 'my_dummy.date', 'my_dummy.range', 'my_dummy.boolean', 'my_dummy.numeric'])]
class EmbeddedDummy
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string|null The dummy name
     *
     * @ORM\Column(nullable=true)
     * @Groups({"embed"})
     */
    private $name;
    /**
     * @var \DateTime|null A dummy date
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    public $dummyDate;
    /**
     * @var EmbeddableDummy
     *
     * @ORM\Embedded(class="EmbeddableDummy")
     * @Groups({"embed"})
     */
    public $embeddedDummy;
    /**
     * @var RelatedDummy|null A related dummy
     *
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
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

    public function getDummyDate(): ?\DateTime
    {
        return $this->dummyDate;
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    public function getRelatedDummy(): ?RelatedDummy
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }
}
