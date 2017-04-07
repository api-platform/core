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
 * Composite Item.
 *
 * @ApiResource
 * @ORM\Entity
 */
class CompositeItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"default"})
     */
    private $field1;

    /**
     * @ORM\OneToMany(targetEntity="CompositeRelation", mappedBy="compositeItem", fetch="EAGER")
     * @Groups({"default"})
     */
    private $compositeValues;

    /**
     * Get id.
     *
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get field1.
     *
     * @return field1
     */
    public function getField1()
    {
        return $this->field1;
    }

    /**
     * Set field1.
     *
     * @param field1 the value to set
     */
    public function setField1($field1)
    {
        $this->field1 = $field1;
    }

    /**
     * Get compositeValues.
     *
     * @return compositeValues
     */
    public function getCompositeValues()
    {
        return $this->compositeValues;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
