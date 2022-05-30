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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Aggregate Offer.
 *
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @ORM\Entity
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummy_products/{id}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct::class, identifiers: ['id'], toProperty: 'product')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummy_products/{id}/related_products/{relatedProducts}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct::class, identifiers: ['id']), 'relatedProducts' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct::class, identifiers: ['id'], toProperty: 'product')], status: 200, operations: [new GetCollection()])]
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
     * @var \Collection<int,\DummyOffer>
     * @ORM\OneToMany(targetEntity="DummyOffer", mappedBy="aggregate", cascade={"persist"})
     */
    private $offers;
    /**
     * @var DummyProduct|null The dummy product
     *
     * @ORM\ManyToOne(targetEntity="DummyProduct", inversedBy="offers")
     */
    private $product;
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

    public function getOffers()
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
        $offer->setAggregate($this);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct(DummyProduct $product)
    {
        $this->product = $product;
    }
}
