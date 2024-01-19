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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Relation.
 */
#[ApiResource]
#[ORM\Entity]
class CompositeRelation
{
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['default'])]
    private ?string $value = null;
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: CompositeItem::class, inversedBy: 'compositeValues')]
    #[ORM\JoinColumn(name: 'composite_item_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['default'])]
    private CompositeItem $compositeItem;
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: CompositeLabel::class)]
    #[ORM\JoinColumn(name: 'composite_label_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['default'])]
    private CompositeLabel $compositeLabel;

    /**
     * Gets value.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Sets value.
     *
     * @param string|null $value the value to set
     */
    public function setValue($value = null): void
    {
        $this->value = $value;
    }

    /**
     * Gets compositeItem.
     */
    public function getCompositeItem(): CompositeItem
    {
        return $this->compositeItem;
    }

    /**
     * Sets compositeItem.
     *
     * @param CompositeItem $compositeItem the value to set
     */
    public function setCompositeItem(CompositeItem $compositeItem): void
    {
        $this->compositeItem = $compositeItem;
    }

    /**
     * Gets compositeLabel.
     */
    public function getCompositeLabel(): CompositeLabel
    {
        return $this->compositeLabel;
    }

    /**
     * Sets compositeLabel.
     *
     * @param CompositeLabel $compositeLabel the value to set
     */
    public function setCompositeLabel(CompositeLabel $compositeLabel): void
    {
        $this->compositeLabel = $compositeLabel;
    }
}
