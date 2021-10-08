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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Answer.
 *
 * @ODM\Document
 * @ApiResource(collectionOperations={
 *     "get_subresource_answer"={"method"="GET", "normalization_context"={"groups"={"foobar"}}}
 * })
 */
class Answer
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     * @Serializer\Groups({"foobar"})
     */
    private $id;

    /**
     * @ODM\Field(nullable=false)
     * @Serializer\Groups({"foobar"})
     */
    private $content;

    /**
     * @ODM\ReferenceOne(targetDocument=Question::class, mappedBy="answer")
     * @Serializer\Groups({"foobar"})
     */
    private $question;

    /**
     * @ODM\ReferenceMany(targetDocument=Question::class, mappedBy="answer")
     * @Serializer\Groups({"foobar"})
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
    public function getId(): ?string
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
