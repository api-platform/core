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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Polymorphism;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[ApiResource(
    operations: [
        new GetCollection(),
    ],
)]
#[DiscriminatorMap(typeProperty: 'bookType', mapping: [
    'fiction' => FictionBook::class,
    'technical' => TechnicalBook::class,
])]
abstract class Book
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $title = '';

    public ?Author $author = null;

    public string $isbn = '';

    public function __construct(string $title = '', ?Author $author = null, string $isbn = '')
    {
        $this->id = null;
        $this->title = $title;
        $this->author = $author ?? new Author('Unknown');
        $this->isbn = $isbn;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): self
    {
        $this->isbn = $isbn;

        return $this;
    }

    abstract public function getBookType(): string;
}
