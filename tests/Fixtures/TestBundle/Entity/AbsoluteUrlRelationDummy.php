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
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(urlGenerationStrategy=UrlGeneratorInterface::ABS_URL)
 * @ORM\Entity
 */
class AbsoluteUrlRelationDummy
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="AbsoluteUrlDummy", mappedBy="absoluteUrlRelationDummy")
     * @ApiSubresource
     */
    public $absoluteUrlDummies;

    public function __construct()
    {
        $this->absoluteUrlDummies = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
