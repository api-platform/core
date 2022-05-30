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
 *
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(normalizationContext: ['groups' => ['foobar']])])]
#[ApiResource(uriTemplate: '/answers/{id}/related_questions/{relatedQuestions}/answer.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'], toProperty: 'answer'), 'relatedQuestions' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question::class, identifiers: ['id'], fromProperty: 'answer')], status: 200, operations: [new Get()])]
#[ApiResource(uriTemplate: '/questions/{id}/answer.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question::class, identifiers: ['id'], fromProperty: 'answer')], status: 200, operations: [new Get()])]
class Answer
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"foobar"})
     */
    private $id;
    /**
     * @ORM\Column(nullable=false)
     * @Serializer\Groups({"foobar"})
     */
    private $content;
    /**
     * @ORM\OneToOne(targetEntity="Question", mappedBy="answer")
     * @Serializer\Groups({"foobar"})
     */
    private $question;
    /**
     * @var \Collection<int,\Question>
     * @ORM\OneToMany(targetEntity="Question", mappedBy="answer")
     * @Serializer\Groups({"foobar"})
     */
    private $relatedQuestions;

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
     *
     * @param Question $question
     */
    public function setQuestion(Question $question = null): self
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
    public function getRelatedQuestions(): Collection
    {
        return $this->relatedQuestions;
    }

    public function addRelatedQuestion(Question $question)
    {
        $this->relatedQuestions->add($question);
    }
}
