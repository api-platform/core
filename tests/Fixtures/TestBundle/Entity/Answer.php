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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Answer.
 *
 * @ORM\Entity
 *
 * @ApiResource(collectionOperations={
 *     "get_subresource_answer"={"method"="GET", "normalization_context"={"groups"={"foobar"}}}
 * })
 */
class Answer
{
    /**
     * @ORM\Column(type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Serializer\Groups({"foobar"})
     */
    private $id;

    /**
     * @ORM\Column(nullable=false)
     *
     * @Serializer\Groups({"foobar"})
     */
    private $content;

    /**
     * @ORM\OneToOne(targetEntity="Question", mappedBy="answer")
     *
     * @Serializer\Groups({"foobar"})
     */
    private $question;

    /**
     * @var Collection<int, Question>
     *
     * @ORM\OneToMany(targetEntity="Question", mappedBy="answer")
     *
     * @Serializer\Groups({"foobar"})
     *
     * @ApiSubresource
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
