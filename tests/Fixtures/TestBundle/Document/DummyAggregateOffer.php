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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Aggregate Offer.
 *
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummy_products/{id}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyProduct::class, identifiers: ['id'], toProperty: 'product')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummy_products/{id}/related_products/{relatedProducts}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyProduct::class, identifiers: ['id']), 'relatedProducts' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyProduct::class, identifiers: ['id'], toProperty: 'product')], status: 200, operations: [new GetCollection()])]
#[ODM\Document]
class DummyAggregateOffer
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ODM\ReferenceMany(targetDocument: DummyOffer::class, mappedBy: 'aggregate', cascade: ['persist'])]
    private ArrayCollection $offers;
    /**
     * @var DummyProduct The dummy product
     */
    #[ODM\ReferenceOne(targetDocument: DummyProduct::class, inversedBy: 'offers', storeAs: 'id')]
    private ?DummyProduct $product = null;
    /**
     * @var int The dummy aggregate offer value
     */
    #[ODM\Field(type: 'int')]
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
