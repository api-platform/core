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

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Embeddable Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @ODM\EmbeddedDocument
 */
class EmbeddableDummy
{
    /**
     * @var string The dummy name
     *
     * @ODM\Field(type="string")
     */
    private $dummyName;

    /**
     * @var bool A dummy boolean
     *
     * @ODM\Field(type="boolean")
     */
    public $dummyBoolean;

    /**
     * @var \DateTime A dummy date
     *
     * @ODM\Field(type="date")
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var string A dummy float
     *
     * @ODM\Field(type="float")
     */
    public $dummyFloat;

    /**
     * @var string A dummy price
     *
     * @ODM\Field(type="float")
     */
    public $dummyPrice;

    /**
     * @ODM\Field(type="string")
     * @Groups({"barcelona", "chicago"})
     */
    protected $symfony;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getDummyName()
    {
        return $this->dummyName;
    }

    /**
     * @param string $dummyName
     */
    public function setDummyName(string $dummyName)
    {
        $this->dummyName = $dummyName;
    }

    /**
     * @return bool
     */
    public function isDummyBoolean()
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean(bool $dummyBoolean)
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    /**
     * @return \DateTime
     */
    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    /**
     * @param \DateTime $dummyDate
     */
    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    /**
     * @return string
     */
    public function getDummyFloat()
    {
        return $this->dummyFloat;
    }

    /**
     * @param string $dummyFloat
     */
    public function setDummyFloat(string $dummyFloat)
    {
        $this->dummyFloat = $dummyFloat;
    }

    /**
     * @return string
     */
    public function getDummyPrice()
    {
        return $this->dummyPrice;
    }

    /**
     * @param string $dummyPrice
     */
    public function setDummyPrice(string $dummyPrice)
    {
        $this->dummyPrice = $dummyPrice;
    }

    /**
     * @return mixed
     */
    public function getSymfony()
    {
        return $this->symfony;
    }

    /**
     * @param mixed $symfony
     */
    public function setSymfony($symfony)
    {
        $this->symfony = $symfony;
    }
}
