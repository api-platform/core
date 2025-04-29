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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\SortComputedFieldFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[GetCollection(
    normalizationContext: ['hydra_prefix' => false],
    paginationItemsPerPage: 3,
    paginationPartial: false,
    stateOptions: new Options(repositoryMethod: 'getCartsWithTotalQuantity'),
    processor: [self::class, 'process'],
    write: true,
    parameters: [
        'sort[:property]' => new QueryParameter(
            filter: new SortComputedFieldFilter(),
            properties: ['totalQuantity'],
            property: 'totalQuantity'
        ),
    ]
)]
class Cart
{
    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        foreach ($data as &$value) {
            $cart = $value[0];
            $cart->totalQuantity = $value['totalQuantity'] ?? 0;
            $value = $cart;
        }

        return $data;
    }

    public ?int $totalQuantity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, CartProduct> the items in this cart
     */
    #[ORM\OneToMany(targetEntity: CartProduct::class, mappedBy: 'cart', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, CartProduct>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartProduct $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setCart($this);
        }

        return $this;
    }

    public function removeItem(CartProduct $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCart() === $this) {
                $item->setCart(null);
            }
        }

        return $this;
    }
}
