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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'runtime_entity_row')]
class EntityRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, AttributeValue> */
    #[ORM\OneToMany(targetEntity: AttributeValue::class, mappedBy: 'entity', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $values;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private ResourceDefinition $definition,
    ) {
        $this->values = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, AttributeValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function replaceValues(array $values): void
    {
        $this->values->clear();
        foreach ($this->definition->getAttributes() as $attribute) {
            $value = $values[$attribute->getName()] ?? null;
            foreach ($attribute->isCollection() ? (array) $value : [$value] as $position => $item) {
                if (null !== $item) {
                    $this->values->add(new AttributeValue($this, $attribute, $position, $item));
                }
            }
        }
    }
}
