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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Embeddable Dummy.
 *
 * @author Jordan Samouh <jordan.samouh@gmail.com>
 *
 * @ORM\Embeddable
 */
class EmbeddableDummy
{
    /**
     * @var string The dummy name
     *
     * @ORM\Column(nullable=true)
     * @Groups({"embed"})
     */
    private $dummyName;

    /**
     * @var bool A dummy boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $dummyBoolean;

    /**
     * @var \DateTime A dummy date
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    public $dummyDate;

    /**
     * @var string A dummy float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $dummyFloat;

    /**
     * @var string A dummy price
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    public $dummyPrice;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"barcelona", "chicago"})
     */
    protected $symfony;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
    }

    public function getDummyName(): string
    {
        return $this->dummyName;
    }

    public function setDummyName(string $dummyName)
    {
        $this->dummyName = $dummyName;
    }

    public function isDummyBoolean(): bool
    {
        return $this->dummyBoolean;
    }

    public function setDummyBoolean(bool $dummyBoolean)
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function getDummyDate(): \DateTime
    {
        return $this->dummyDate;
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyFloat(): string
    {
        return $this->dummyFloat;
    }

    public function setDummyFloat(string $dummyFloat)
    {
        $this->dummyFloat = $dummyFloat;
    }

    public function getDummyPrice(): string
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
