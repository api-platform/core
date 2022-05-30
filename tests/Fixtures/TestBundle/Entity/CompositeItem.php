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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Item.
 *
 * @ORM\Entity
 */
#[ApiResource]
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
     * Gets id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets field1.
     */
    public function getField1(): ?string
    {
        return $this->field1;
    }

    /**
     * Sets field1.
     *
     * @param string|null $field1 the value to set
     */
    public function setField1($field1 = null)
    {
        $this->field1 = $field1;
    }

    /**
     * Gets compositeValues.
     */
    public function getCompositeValues(): ?Collection
    {
        return $this->compositeValues;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
