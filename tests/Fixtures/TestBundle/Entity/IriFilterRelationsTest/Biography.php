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
use Doctrine\ORM\Mapping as ORM;

/**
 * Biography entity for IriFilter relations testing - OneToOne inverse side.
 */
#[ORM\Entity]
#[ORM\Table(name: 'iri_filter_relations_biography')]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'author' => new QueryParameter(filter: new IriFilter()),
            ]
        ),
    ]
)]
class Biography
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\OneToOne(targetEntity: Author::class, mappedBy: 'biography')]
    private ?Author $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        if (null === $author && null !== $this->author) {
            $this->author->setBiography(null);
        }

        $this->author = $author;

        if (null !== $author && $author->getBiography() !== $this) {
            $author->setBiography($this);
        }

        return $this;
    }
}
