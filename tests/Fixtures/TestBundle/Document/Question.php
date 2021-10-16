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
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ApiResource
 */
class Question
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\Field(nullable=true)
     */
    private $content;

    /**
     * @ODM\ReferenceOne(targetDocument=Answer::class, inversedBy="question", storeAs="id")
     * @ApiSubresource
     */
    private $answer;

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
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set answer.
     */
    public function setAnswer(Answer $answer = null): self
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer.
     */
    public function getAnswer(): ?Answer
    {
        return $this->answer;
    }
}
