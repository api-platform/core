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
     * @ODM\Id(strategy="INCREMENT", type="integer")
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
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Answer
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set question.
     *
     * @param Question $question
     *
     * @return Answer
     */
    public function setQuestion(Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Get related question.
     *
     * @return ArrayCollection
     */
    public function getRelatedQuestions()
    {
        return $this->relatedQuestions;
    }

    public function addRelatedQuestion(Question $question)
    {
        $this->relatedQuestions->add($question);
    }
}
