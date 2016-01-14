<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
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
     * Get composite id.
     *
     * @return string
     */
    public function getId()
    {
        return sprintf('%s-%s', $this->compositeItem->getId(), $this->compositeLabel->getId());
    }

    /**
     * Get value.
     *
     * @return value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param value the value to set.
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get compositeItem.
     *
     * @return compositeItem.
     */
    public function getCompositeItem()
    {
        return $this->compositeItem;
    }

    /**
     * Set compositeItem.
     *
     * @param compositeItem the value to set.
     */
    public function setCompositeItem($compositeItem)
    {
        $this->compositeItem = $compositeItem;
    }

    /**
     * Get compositeLabel.
     *
     * @return compositeLabel.
     */
    public function getCompositeLabel()
    {
        return $this->compositeLabel;
    }

    /**
     * Set compositeLabel.
     *
     * @param compositeLabel the value to set.
     */
    public function setCompositeLabel($compositeLabel)
    {
        $this->compositeLabel = $compositeLabel;
    }
}
