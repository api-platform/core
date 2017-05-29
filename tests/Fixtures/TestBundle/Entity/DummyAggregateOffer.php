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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Aggregate Offer.
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource
 * @ORM\Entity
 */
class DummyAggregateOffer
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
     * @var ArrayCollection
     *
     * @ApiProperty(subresource=true)
     * @ORM\OneToMany(targetEntity="DummyOffer", mappedBy="id", cascade={"persist"})
     */
    private $offers;

    /**
     * @var int The dummy aggregate offer value
     *
     * @ORM\Column(type="integer")
     */
    private $value;

    public function __construct()
    {
        $this->offers = new ArrayCollection();
    }

    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function setOffers($offers)
    {
        $this->offers = $offers;
    }

    public function addOffer(DummyOffer $offer)
    {
        $this->offers->add($offer);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value)
    {
        $this->value = $value;
    }
}
