<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Label.
 *
 * @ApiResource
 * @ORM\Entity
 */
class CompositeLabel
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
    private $value;

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

    public function __toString()
    {
        return (string) $this->id;
    }
}
