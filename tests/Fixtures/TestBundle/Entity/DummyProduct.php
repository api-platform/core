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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Product.
 *
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummy_products/{id}/related_products.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])], status: 200, operations: [new GetCollection()])]
#[ORM\Entity]
class DummyProduct
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var Collection<int, DummyAggregateOffer>
     */
    #[ORM\OneToMany(targetEntity: DummyAggregateOffer::class, mappedBy: 'product', cascade: ['persist'])]
    private Collection $offers;
    /**
     * @var string The tour name
     */
    #[ORM\Column]
    private $name;
    /**
     * @var Collection<int,DummyProduct>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $relatedProducts;
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'relatedProducts')]
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

    public function setRelatedProducts(iterable $relatedProducts)
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
