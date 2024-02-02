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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Answer.
 */
#[ApiResource(operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(normalizationContext: ['groups' => ['foobar']])])]
#[ApiResource(uriTemplate: '/answers/{id}/related_questions/{relatedQuestions}/answer{._format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'], toProperty: 'answer'), 'relatedQuestions' => new Link(fromClass: Question::class, identifiers: ['id'], fromProperty: 'answer')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/questions/{id}/answer{._format}', uriVariables: ['id' => new Link(fromClass: Question::class, identifiers: ['id'], fromProperty: 'answer')], status: 200, operations: [new Get()])]
#[ORM\Entity]
class Answer
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Serializer\Groups(['foobar'])]
    private ?int $id = null;
    #[ORM\Column(nullable: false)]
    #[Serializer\Groups(['foobar'])]
    private ?string $content = null;
    #[ORM\OneToOne(targetEntity: Question::class, mappedBy: 'answer')]
    #[Serializer\Groups(['foobar'])]
    private ?Question $question = null;
    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'answer')]
    #[Serializer\Groups(['foobar'])]
    private Collection|iterable $relatedQuestions;

    public function __construct()
    {
        $this->relatedQuestions = new ArrayCollection();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set content.
     *
     * @param string $content
     */
    public function setContent($content): self
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
     * Set question.
     */
    public function setQuestion(?Question $question = null): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     */
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    /**
     * Get related question.
     */
    public function getRelatedQuestions(): Collection|iterable
    {
        return $this->relatedQuestions;
    }

    public function addRelatedQuestion(Question $question): void
    {
        $this->relatedQuestions->add($question);
    }
}
