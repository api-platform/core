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
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class Container
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     *
     * @var string UUID
     */
    private $id;

    /**
     * @ApiSubresource
     * @ORM\OneToMany(
     *     targetEntity="Node",
     *     mappedBy="container",
     *     indexBy="serial",
     *     fetch="LAZY",
     *     cascade={},
     *     orphanRemoval=false
     * )
     * @ORM\OrderBy({"serial"="ASC"})
     *
     * @var ArrayCollection|Node[]
     */
    private $nodes;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return ArrayCollection|Node[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
