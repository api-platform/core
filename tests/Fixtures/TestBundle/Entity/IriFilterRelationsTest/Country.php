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
 * Country entity for IriFilter relations testing - leaf entity for deep nesting.
 */
#[ORM\Entity]
#[ORM\Table(name: 'iri_filter_relations_country')]
#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'publisher' => new QueryParameter(filter: new IriFilter()),
            ]
        ),
    ]
)]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\OneToMany(targetEntity: Publisher::class, mappedBy: 'country')]
    private Collection $publishers;

    public function __construct()
    {
        $this->publishers = new ArrayCollection();
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

    public function getPublishers(): Collection
    {
        return $this->publishers;
    }

    public function addPublisher(Publisher $publisher): self
    {
        if (!$this->publishers->contains($publisher)) {
            $this->publishers[] = $publisher;
            $publisher->setCountry($this);
        }

        return $this;
    }

    public function removePublisher(Publisher $publisher): self
    {
        if ($this->publishers->removeElement($publisher)) {
            if ($publisher->getCountry() === $this) {
                $publisher->setCountry(null);
            }
        }

        return $this;
    }
}
