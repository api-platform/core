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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Study.
 */
#[ApiResource(
    normalizationContext: ['groups' => ['study:read']],
    denormalizationContext: ['groups' => ['study:write']],
)]
#[ODM\Document]
class Study
{
    #[ODM\Id(type: 'object_id', strategy: 'NONE')]
    #[Groups(['study:read', 'study:write'])]
    private $id;

    #[ODM\Field(nullable: true)]
    #[Groups(['study:read', 'study:write'])]
    private string $content;

    #[ODM\ReferenceMany(storeAs: 'id', targetDocument: Analysis::class, mappedBy: 'study')]
    public Collection|iterable $analyses;

    public function __construct()
    {
        $this->analyses = new ArrayCollection();
    }

    /**
     * Set content.
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get id.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set id.
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get analyses.
     */
    public function getAnalyses(): Collection|iterable
    {
        return $this->analyses;
    }
}
