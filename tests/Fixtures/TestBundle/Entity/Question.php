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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ApiResource
 */
class Question
{
    /**
     * @ORM\Column(type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(nullable=true)
     */
    private $content;

    /**
     * @ORM\OneToOne(targetEntity="Answer", inversedBy="question")
     *
     * @ORM\JoinColumn(name="answer_id", referencedColumnName="id", unique=true)
     *
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
    public function getContent(): ?string
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
