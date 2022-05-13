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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Relation.
 */
#[ApiResource]
#[ODM\Document]
class CompositeRelation
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $id;
    #[Groups(['default'])]
    #[ODM\Field(type: 'string', nullable: true)]
    private $value;
    #[Groups(['default'])]
    #[ODM\ReferenceOne(targetDocument: CompositeItem::class, inversedBy: 'compositeValues')]
    private $compositeItem;
    #[Groups(['default'])]
    #[ODM\ReferenceOne(targetDocument: CompositeLabel::class)]
    private $compositeLabel;

    /**
     * Gets id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

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
    public function setValue($value = null)
    {
        $this->value = $value;
    }

    /**
     * Gets compositeItem.
     */
    public function getCompositeItem(): ?CompositeItem
    {
        return $this->compositeItem;
    }

    /**
     * Sets compositeItem.
     *
     * @param CompositeItem $compositeItem the value to set
     */
    public function setCompositeItem(CompositeItem $compositeItem)
    {
        $this->compositeItem = $compositeItem;
    }

    /**
     * Gets compositeLabel.
     */
    public function getCompositeLabel(): ?CompositeLabel
    {
        return $this->compositeLabel;
    }

    /**
     * Sets compositeLabel.
     *
     * @param CompositeLabel $compositeLabel the value to set
     */
    public function setCompositeLabel(CompositeLabel $compositeLabel)
    {
        $this->compositeLabel = $compositeLabel;
    }
}
