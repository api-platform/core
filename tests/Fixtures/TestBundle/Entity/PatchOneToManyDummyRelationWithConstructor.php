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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get"
 *     }
 * )
 * @ORM\Entity
 */
class PatchOneToManyDummyRelationWithConstructor
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="PatchOneToManyDummy", inversedBy="relations")
     */
    protected $related;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRelated(): ?PatchOneToManyDummy
    {
        return $this->related;
    }

    public function setRelated(?PatchOneToManyDummy $related): void
    {
        $this->related = $related;
    }
}
