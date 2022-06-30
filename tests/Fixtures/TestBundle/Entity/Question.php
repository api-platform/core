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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ApiResource(uriTemplate: '/answers/{id}/related_questions.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer::class, identifiers: ['id'], toProperty: 'answer')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/questions/{id}/answer/related_questions.{_format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'], fromProperty: 'answer'), 'answer' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\Answer::class, identifiers: [], expandedValue: 'answer', toProperty: 'answer')], status: 200, operations: [new GetCollection()])]
#[ORM\Entity]
class Question
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;
    #[ORM\Column(nullable: true)]
    private $content;
    #[ORM\OneToOne(targetEntity: Answer::class, inversedBy: 'question')]
    #[ORM\JoinColumn(name: 'answer_id', referencedColumnName: 'id', unique: true)]
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
