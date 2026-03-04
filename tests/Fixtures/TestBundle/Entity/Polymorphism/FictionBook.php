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
class FictionBook extends Book
{
    public const string BOOK_TYPE = 'fiction';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pageCount = null;

    public function __construct(
        string $title = '',
        ?Author $author = null,
        string $isbn = '',
        ?string $genre = null,
        ?int $pageCount = null
    ) {
        parent::__construct($title, $author, $isbn);
        $this->genre = $genre;
        $this->pageCount = $pageCount;
    }

    public function getBookType(): string
    {
        return self::BOOK_TYPE;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;
        return $this;
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): self
    {
        $this->pageCount = $pageCount;
        return $this;
    }
}
