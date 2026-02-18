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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\IriFilterRelationsTest;

use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Author entity for IriFilter relations testing - central entity with all relation types.
 */
#[ORM\Entity]
#[ORM\Table(name: 'iri_filter_relations_author')]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'profile' => new QueryParameter(filter: new IriFilter()),
                'biography' => new QueryParameter(filter: new IriFilter()),
                'book' => new QueryParameter(filter: new IriFilter(), property: 'books'),
                'publisher' => new QueryParameter(filter: new IriFilter()),
                'publisherCountry' => new QueryParameter(filter: new IriFilter(), property: 'publisher.country'),
                'bookPublisher' => new QueryParameter(filter: new IriFilter(), property: 'books.publisher'),
            ]
        ),
    ]
)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\OneToOne(targetEntity: Profile::class, inversedBy: 'author')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    #[ORM\OneToOne(targetEntity: Biography::class, inversedBy: 'author')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Biography $biography = null;

    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'authors')]
    private Collection $books;

    #[ORM\ManyToOne(targetEntity: Publisher::class, inversedBy: 'authors')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Publisher $publisher = null;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;

        if (null !== $profile && $profile->getAuthor() !== $this) {
            $profile->setAuthor($this);
        }

        return $this;
    }

    public function getBiography(): ?Biography
    {
        return $this->biography;
    }

    public function setBiography(?Biography $biography): self
    {
        if (null === $biography && null !== $this->biography) {
            $this->biography->setAuthor(null);
        }

        $this->biography = $biography;

        if (null !== $biography && $biography->getAuthor() !== $this) {
            $biography->setAuthor($this);
        }

        return $this;
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books[] = $book;
            $book->addAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->books->removeElement($book)) {
            $book->removeAuthor($this);
        }

        return $this;
    }

    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher): self
    {
        $this->publisher = $publisher;

        return $this;
    }
}
