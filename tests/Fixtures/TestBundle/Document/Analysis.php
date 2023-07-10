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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Analysis.
 */
#[ApiResource(
    uriTemplate: '/studies/{studyId}/analyses',
    operations: [
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    uriVariables: [
        'studyId' => new Link(toProperty: 'study', fromClass: Study::class, identifiers: ['id']),
    ],
    normalizationContext: ['groups' => ['analysis:read']],
    denormalizationContext: ['groups' => ['analysis:write']],
)]
#[ODM\Document]
class Analysis
{
    #[ODM\Id]
    #[Groups(['analysis:read', 'analysis:write'])]
    private $id;

    #[ODM\Field(nullable: false)]
    #[Groups(['analysis:read', 'analysis:write'])]
    private ?string $content = null;

    #[ODM\ReferenceOne(storeAs: 'id', targetDocument: Study::class, inversedBy: 'analyses')]
    #[Groups(['analysis:read', 'analysis:write'])]
    private ?Study $study = null;

    /**
     * Get id.
     */
    public function getId(): ?string
    {
        return $this->id;
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
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set study.
     */
    public function setStudy(Study $study = null): self
    {
        $this->study = $study;

        return $this;
    }

    /**
     * Get study.
     */
    public function getStudy(): ?Study
    {
        return $this->study;
    }
}
