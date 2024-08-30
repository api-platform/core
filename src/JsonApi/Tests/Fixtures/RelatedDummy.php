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

namespace ApiPlatform\JsonApi\Tests\Fixtures;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource]
class RelatedDummy implements \Stringable
{
    #[ApiProperty(writable: false)]
    #[Groups(['chicago', 'friends'])]
    private $id;

    /**
     * @var string|null A name
     */
    #[ApiProperty(iris: ['RelatedDummy.name'])]
    #[Groups(['friends'])]
    public $name;

    #[ApiProperty(deprecationReason: 'This property is deprecated for upgrade test')]
    #[Groups(['barcelona', 'chicago', 'friends'])]
    protected $symfony = 'symfony';

    /**
     * @var \DateTime|null A dummy date
     */
    #[Groups(['friends'])]
    public $dummyDate;

    /**
     * @var bool|null A dummy bool
     */
    #[Groups(['friends'])]
    public ?bool $dummyBoolean = null;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony): void
    {
        $this->symfony = $symfony;
    }

    public function setDummyDate(\DateTime $dummyDate): void
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean): void
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
