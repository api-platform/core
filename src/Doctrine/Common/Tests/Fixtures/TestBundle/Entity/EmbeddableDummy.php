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

namespace ApiPlatform\Doctrine\Common\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Embeddable Dummy.
 *
 * @author Jordan Samouh <jordan.samouh@gmail.com>
 */
#[ORM\Embeddable]
class EmbeddableDummy
{
    /**
     * @var string The dummy name
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['embed'])]
    private ?string $dummyName = null;
    /**
     * @var bool|null A dummy boolean
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    public ?bool $dummyBoolean = null;
    /**
     * @var \DateTime|null A dummy date
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\DateTime]
    public ?\DateTime $dummyDate = null;
    /**
     * @var float|null A dummy float
     */
    #[ORM\Column(type: 'float', nullable: true)]
    public ?float $dummyFloat = null;
    /**
     * @var string|null A dummy price
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $dummyPrice = null;
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['barcelona', 'chicago'])]
    protected $symfony;

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

    public function getDummyPrice(): ?string
    {
        return $this->dummyPrice;
    }

    public function setDummyPrice(string $dummyPrice): void
    {
        $this->dummyPrice = $dummyPrice;
    }

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony): void
    {
        $this->symfony = $symfony;
    }
}
