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

/**
 * @see https://github.com/api-platform/core/pull/904#issuecomment-294132077
 * @ApiResource(graphql={})
 * @ORM\Entity
 */
class Node
{
    /**
     * @ORM\Id
     * @ORM\Column(name="serial", type="integer")
     *
     * @var int Node serial
     */
    private $serial;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Container", fetch="LAZY")
     * @ORM\JoinColumn(name="container_id", referencedColumnName="id", onDelete="RESTRICT")
     *
     * @var Container
     */
    private $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setSerial(int $serial)
    {
        $this->serial = $serial;
    }

    public function getSerial(): int
    {
        return $this->serial;
    }
}
