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
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Product.
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource
 * @ODM\Document
 */
class DummyProduct
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var Collection
     *
     * @ApiSubresource
     * @ODM\ReferenceMany(targetDocument=DummyAggregateOffer::class, mappedBy="product", cascade={"persist"})
     */
    private $offers;

    /**
     * @var string The tour name
     *
     * @ODM\Field
     */
    private $name;

    /**
     * @var Collection
     *
     * @ApiSubresource
     * @ODM\ReferenceMany(targetDocument=DummyProduct::class, mappedBy="parent")
     */
    private $relatedProducts;

    /**
     * @ODM\ReferenceOne(targetDocument=DummyProduct::class, inversedBy="relatedProducts")
     */
    private $parent;

    public function __construct()
    {
        $this->offers = new ArrayCollection();
        $this->relatedProducts = new ArrayCollection();
    }

    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function setOffers($offers)
    {
        $this->offers = $offers;
    }

    public function addOffer(DummyAggregateOffer $offer)
    {
        $this->offers->add($offer);
        $offer->setProduct($this);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getRelatedProducts(): Collection
    {
        return $this->relatedProducts;
    }

    public function setRelatedProducts(Collection $relatedProducts)
    {
        $this->relatedProducts = $relatedProducts;
    }

    public function addRelatedProduct(self $relatedProduct)
    {
        $this->relatedProducts->add($relatedProduct);
        $relatedProduct->setParent($this);
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(self $product)
    {
        $this->parent = $product;
    }
}
