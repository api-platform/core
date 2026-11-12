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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['nested_input_read']],
    denormalizationContext: ['groups' => ['nested_input_write']],
    graphQlOperations: [
        new Query(),
        new Mutation(name: 'create'),
    ],
)]
#[ORM\Entity]
class NestedInputOwner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['nested_input_read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    #[Groups(['nested_input_read', 'nested_input_write'])]
    public string $name = '';

    /**
     * @var Collection<int, NestedInputItem>
     */
    #[ORM\ManyToMany(targetEntity: NestedInputItem::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'nested_input_owner_items')]
    #[ORM\InverseJoinColumn(unique: true)]
    #[Groups(['nested_input_write'])]
    public Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addItem(NestedInputItem $item): void
    {
        $this->items->add($item);
    }

    public function removeItem(NestedInputItem $item): void
    {
        $this->items->removeElement($item);
    }
}
