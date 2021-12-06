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
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Fourth Level.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource
 * @ORM\Entity
 */
class FourthLevel
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Groups({"barcelona", "chicago"})
     */
    private $level = 4;

    /**
     * @ORM\OneToMany(targetEntity=ThirdLevel::class, cascade={"persist"}, mappedBy="badFourthLevel")
     */
    public $badThirdLevel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level)
    {
        $this->level = $level;
    }
}
