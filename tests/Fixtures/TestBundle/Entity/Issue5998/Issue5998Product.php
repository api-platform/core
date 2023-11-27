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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource]
#[Post(
    denormalizationContext: ['groups' => ['product:write']],
    input: SaveProduct::class,
)]
class Issue5998Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, ProductCode>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductCode::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $codes;

    public function __construct()
    {
        $this->codes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, ProductCode>
     */
    public function getCodes(): Collection
    {
        return $this->codes;
    }

    public function addCode(ProductCode $code): void
    {
        if (!$this->codes->contains($code)) {
            $this->codes->add($code);
            $code->setProduct($this);
        }
    }

    public function removeCode(ProductCode $code): void
    {
        if ($this->codes->removeElement($code)) {
            // set the owning side to null (unless already changed)
            if ($code->getProduct() === $this) {
                $code->setProduct(null);
            }
        }
    }
}
