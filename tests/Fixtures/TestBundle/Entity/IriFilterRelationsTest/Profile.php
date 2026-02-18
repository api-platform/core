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
 * Profile entity for IriFilter relations testing - OneToOne owning side.
 */
#[ORM\Entity]
#[ORM\Table(name: 'iri_filter_relations_profile')]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'author' => new QueryParameter(filter: new IriFilter()),
                'authorPublisher' => new QueryParameter(filter: new IriFilter(), property: 'author.publisher'),
            ]
        ),
    ]
)]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $bio;

    #[ORM\OneToOne(targetEntity: Author::class, mappedBy: 'profile')]
    private ?Author $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBio(): string
    {
        return $this->bio;
    }

    public function setBio(string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        if (null === $author && null !== $this->author) {
            $this->author->setProfile(null);
        }

        $this->author = $author;

        if (null !== $author && $author->getProfile() !== $this) {
            $author->setProfile($this);
        }

        return $this;
    }
}
