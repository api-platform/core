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

namespace ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alexandre Delplace <alexandre.delplacemille@gmail.com>
 */
#[ODM\Document]
class ThirdLevel
{
    /**
     * @var int|null The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ODM\Field(type: 'int')]
    private int $level = 3;
    #[ODM\Field(type: 'bool')]
    private bool $test = true;
    #[ODM\ReferenceOne(targetDocument: FourthLevel::class, cascade: ['persist'], storeAs: 'id')]
    public ?FourthLevel $fourthLevel = null;
    #[ODM\ReferenceOne(targetDocument: FourthLevel::class, cascade: ['persist'])]
    public $badFourthLevel;
    #[ODM\ReferenceMany(mappedBy: 'thirdLevel', targetDocument: RelatedDummy::class)]
    public Collection|iterable $relatedDummies;

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    public function setTest(bool $test): void
    {
        $this->test = $test;
    }

    public function getFourthLevel(): ?FourthLevel
    {
        return $this->fourthLevel;
    }

    public function setFourthLevel(?FourthLevel $fourthLevel = null): void
    {
        $this->fourthLevel = $fourthLevel;
    }
}
