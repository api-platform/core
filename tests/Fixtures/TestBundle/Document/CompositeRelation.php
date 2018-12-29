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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Relation.
 *
 * @ApiResource
 * @ODM\Document
 */
class CompositeRelation
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @ODM\Field(type="string", nullable=true)
     * @Groups({"default"})
     */
    private $value;

    /**
     * @ODM\ReferenceOne(targetDocument=CompositeItem::class, inversedBy="compositeValues")
     * @Groups({"default"})
     */
    private $compositeItem;

    /**
     * @ODM\ReferenceOne(targetDocument=CompositeLabel::class)
     * @Groups({"default"})
     */
    private $compositeLabel;

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets value.
     *
     * @return string|null
     */
    public function getValue()
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
     *
     * @return CompositeItem
     */
    public function getCompositeItem()
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
     *
     * @return CompositeLabel
     */
    public function getCompositeLabel()
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
