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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Fourth Level.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource
 *
 * @ODM\Document
 */
class FourthLevel
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var int|null
     *
     * @ODM\Field(type="int")
     *
     * @Groups({"barcelona", "chicago"})
     */
    private $level = 4;

    /**
     * @ODM\ReferenceMany(targetDocument=ThirdLevel::class, cascade={"persist"}, mappedBy="badFourthLevel", storeAs="id")
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
