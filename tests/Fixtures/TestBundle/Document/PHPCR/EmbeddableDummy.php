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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\PHPCR;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Embeddable Dummy.
 *
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 *
 * @PHPCRODM\Document(referenceable=true)
 */
class EmbeddableDummy
{
    /**
     * @var int The id
     *
     * @PHPCRODM\Id
     */
    private $id;

    /**
     * @PHPCRODM\Uuid
     **/
    private $uuid;

    /**
     * @PHPCRODM\Node
     */
    public $node;

    /**
     * @PHPCRODM\ParentDocument()
     */
    public $parentDocument;

    /**
     * @var string The dummy name
     *
     * @PHPCRODM\Field(type="string")
     */
    private $dummyName;

    /**
     * @var bool A dummy boolean
     *
     * @PHPCRODM\Field(type="boolean")
     */
    public $dummyBoolean;

    /**
     * @var \DateTime A dummy date
     *
     * @PHPCRODM\Field(type="date")
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var string A dummy float
     *
     * @PHPCRODM\Field(type="float")
     */
    public $dummyFloat;

    /**
     * @var string A dummy price
     *
     * @PHPCRODM\Field(type="float")
     */
    public $dummyPrice;

    /**
     * @PHPCRODM\Field(type="string")
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

    public function setDummyPrice(string $dummyPrice)
    {
        $this->dummyPrice = $dummyPrice;
    }

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony)
    {
        $this->symfony = $symfony;
    }
}
