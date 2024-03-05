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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[ApiResource(uriTemplate: '/answers/{id}/related_questions{._format}', uriVariables: ['id' => new Link(fromClass: Answer::class, identifiers: ['id'], toProperty: 'answer')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/questions/{id}/answer/related_questions{._format}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'], fromProperty: 'answer'), 'answer' => new Link(fromClass: Answer::class, identifiers: [], expandedValue: 'answer', toProperty: 'answer')], status: 200, operations: [new GetCollection()])]
#[ODM\Document]
class Question
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ODM\Field(nullable: true)]
    private string $content;
    #[ODM\ReferenceOne(targetDocument: Answer::class, inversedBy: 'question', storeAs: 'id')]
    private ?Answer $answer = null;

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
    public function setAnswer(?Answer $answer = null): self
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
