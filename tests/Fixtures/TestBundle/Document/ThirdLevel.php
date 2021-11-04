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
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ApiResource
 * @ODM\Document
 */
class ThirdLevel
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     * @Groups({"barcelona", "chicago"})
     */
    private $level = 3;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    private $test = true;

    /**
     * @ApiSubresource
     * @ODM\ReferenceOne(targetDocument=FourthLevel::class, cascade={"persist"}, storeAs="id")
     * @Groups({"barcelona", "chicago", "friends"})
     */
    public $fourthLevel;

    /**
     * @ODM\ReferenceOne(targetDocument=FourthLevel::class, cascade={"persist"})
     */
    public $badFourthLevel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level)
    {
        $this->level = $level;
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    public function setTest(bool $test)
    {
        $this->test = $test;
    }

    public function getFourthLevel(): ?FourthLevel
    {
        return $this->fourthLevel;
    }

    public function setFourthLevel(FourthLevel $fourthLevel = null)
    {
        $this->fourthLevel = $fourthLevel;
    }
}
