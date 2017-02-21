<?php

declare(strict_types=1);
/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class Container
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="guid")
     *
     * @var string UUID
     */
    private $id;

    /**
     * @ApiProperty(subresource=true)
     * @ORM\OneToMany(
     *      targetEntity="Node",
     *      mappedBy="container",
     *      indexBy="serial",
     *      fetch="LAZY",
     *      cascade={},
     *      orphanRemoval=false
     * )
     * @ORM\OrderBy({"serial"="ASC"})
     *
     * @var Collection|Node[]
     */
    private $nodes;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return array|Node[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
