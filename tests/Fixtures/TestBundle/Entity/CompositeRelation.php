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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Relation.
 *
 * @ApiResource
 * @ORM\Entity
 */
class CompositeRelation
{
    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"default"})
     */
    private $value;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="CompositeItem", inversedBy="compositeValues")
     * @ORM\JoinColumn(name="composite_item_id", referencedColumnName="id", nullable=false)
     * @Groups({"default"})
     */
    private $compositeItem;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="CompositeLabel")
     * @ORM\JoinColumn(name="composite_label_id", referencedColumnName="id", nullable=false)
     * @Groups({"default"})
     */
    private $compositeLabel;

    /**
     * Get value.
     *
     * @return value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param value the value to set
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get compositeItem.
     *
     * @return compositeItem
     */
    public function getCompositeItem()
    {
        return $this->compositeItem;
    }

    /**
     * Set compositeItem.
     *
     * @param compositeItem the value to set
     */
    public function setCompositeItem($compositeItem)
    {
        $this->compositeItem = $compositeItem;
    }

    /**
     * Get compositeLabel.
     *
     * @return compositeLabel
     */
    public function getCompositeLabel()
    {
        return $this->compositeLabel;
    }

    /**
     * Set compositeLabel.
     *
     * @param compositeLabel the value to set
     */
    public function setCompositeLabel($compositeLabel)
    {
        $this->compositeLabel = $compositeLabel;
    }
}
