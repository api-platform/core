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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Polymorphism;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TechnicalBook extends Book
{
    public const string BOOK_TYPE = 'technical';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $programmingLanguage = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $difficultyLevel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $topic = null;

    public function __construct(
        string $title = '',
        ?Author $author = null,
        string $isbn = '',
        ?string $programmingLanguage = null,
        ?string $difficultyLevel = null,
        ?string $topic = null
    ) {
        parent::__construct($title, $author, $isbn);
        $this->programmingLanguage = $programmingLanguage;
        $this->difficultyLevel = $difficultyLevel;
        $this->topic = $topic;
    }

    public function getBookType(): string
    {
        return self::BOOK_TYPE;
    }

    public function getProgrammingLanguage(): ?string
    {
        return $this->programmingLanguage;
    }

    public function setProgrammingLanguage(?string $programmingLanguage): self
    {
        $this->programmingLanguage = $programmingLanguage;
        return $this;
    }

    public function getDifficultyLevel(): ?string
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?string $difficultyLevel): self
    {
        $this->difficultyLevel = $difficultyLevel;
        return $this;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(?string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }
}
