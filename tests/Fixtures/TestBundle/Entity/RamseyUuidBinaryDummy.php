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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ApiResource
 * @ApiFilter(SearchFilter::class, properties={"id"="exact", "relateds"="exact"})
 */
class RamseyUuidBinaryDummy
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid_binary", unique=true)
     */
    private $id;

    /**
     * @var Collection<RamseyUuidBinaryDummy>
     *
     * @ORM\OneToMany(targetEntity="RamseyUuidBinaryDummy", mappedBy="relatedParent")
     */
    private $relateds;

    /**
     * @var ?RamseyUuidBinaryDummy
     *
     * @ORM\ManyToOne(targetEntity="RamseyUuidBinaryDummy", inversedBy="relateds")
     */
    private $relatedParent;

    public function __construct()
    {
        $this->relateds = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(string $uuid): void
    {
        $this->id = Uuid::fromString($uuid);
    }

    public function getRelateds(): Collection
    {
        return $this->relateds;
    }

    public function addRelated(self $dummy): void
    {
        $this->relateds->add($dummy);
        $dummy->setRelatedParent($this);
    }

    public function getRelatedParent(): ?self
    {
        return $this->relatedParent;
    }

    public function setRelatedParent(self $dummy): void
    {
        $this->relatedParent = $dummy;
    }
}
