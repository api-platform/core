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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource
 * @ODM\Document
 */
class DummyWithEmbedManyOmittingTargetDocument
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var EmbeddableDummy[]|Collection
     *
     * @ODM\EmbedMany
     */
    private $embeddedDummies;

    public function __construct()
    {
        $this->embeddedDummies = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmbeddedDummies(): Collection
    {
        return $this->embeddedDummies;
    }

    public function addEmbeddedDummy(EmbeddableDummy $dummy): void
    {
        $this->embeddedDummies->add($dummy);
    }

    public function removeEmbeddedDummy(EmbeddableDummy $dummy): void
    {
        $this->embeddedDummies->removeElement($dummy);
    }
}
