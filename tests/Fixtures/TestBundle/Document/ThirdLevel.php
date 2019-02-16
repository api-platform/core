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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

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
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ODM\Field(type="integer")
     * @Groups({"barcelona", "chicago"})
     */
    private $level = 3;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return bool
     */
    public function isTest()
    {
        return $this->test;
    }

    /**
     * @param bool $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    /**
     * @return FourthLevel|null
     */
    public function getFourthLevel()
    {
        return $this->fourthLevel;
    }

    public function setFourthLevel(FourthLevel $fourthLevel = null)
    {
        $this->fourthLevel = $fourthLevel;
    }
}
