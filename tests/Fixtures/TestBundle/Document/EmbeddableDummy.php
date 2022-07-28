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

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Embeddable Dummy.
 *
 * @author Jordan Samouh <jordan.samouh@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 */
#[ODM\EmbeddedDocument]
class EmbeddableDummy
{
    /**
     * @var string|null The dummy name
     */
    #[Groups(['embed'])]
    #[ODM\Field(type: 'string')]
    private ?string $dummyName = null;
    /**
     * @var bool|null A dummy boolean
     */
    #[ODM\Field(type: 'bool')]
    public ?bool $dummyBoolean = null;
    /**
     * @var \DateTime|null A dummy date
     */
    #[Assert\DateTime]
    #[ODM\Field(type: 'date')]
    public ?\DateTime $dummyDate = null;
    /**
     * @var float|null A dummy float
     */
    #[ODM\Field(type: 'float')]
    public ?float $dummyFloat = null;
    /**
     * @var float|null A dummy price
     */
    #[ODM\Field(type: 'float')]
    public ?float $dummyPrice = null;
    #[Groups(['barcelona', 'chicago'])]
    #[ODM\Field(type: 'string')]
    protected ?string $symfony = null;

    public static function staticMethod(): void
    {
    }

    public function __construct()
    {
    }

    public function getDummyName(): ?string
    {
        return $this->dummyName;
    }

    public function setDummyName(string $dummyName): void
    {
        $this->dummyName = $dummyName;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    public function setDummyBoolean(bool $dummyBoolean): void
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function getDummyDate(): ?\DateTime
    {
        return $this->dummyDate;
    }

    public function setDummyDate(\DateTime $dummyDate): void
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyFloat(): ?float
    {
        return $this->dummyFloat;
    }

    public function setDummyFloat(float $dummyFloat): void
    {
        $this->dummyFloat = $dummyFloat;
    }

    public function getDummyPrice(): ?float
    {
        return $this->dummyPrice;
    }

    public function setDummyPrice(float $dummyPrice): void
    {
        $this->dummyPrice = $dummyPrice;
    }

    public function getSymfony(): ?string
    {
        return $this->symfony;
    }

    public function setSymfony(string $symfony): void
    {
        $this->symfony = $symfony;
    }
}
