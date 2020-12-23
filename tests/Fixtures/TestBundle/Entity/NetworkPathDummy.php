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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(urlGenerationStrategy=UrlGeneratorInterface::NET_PATH)
 * @ORM\Entity
 */
class NetworkPathDummy
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="NetworkPathRelationDummy", inversedBy="networkPathDummies")
     */
    public $networkPathRelationDummy;

    public function getId()
    {
        return $this->id;
    }
}
