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
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ApiResource(
    uriTemplate: '/one_to_one_subresource_questions/{id}/answer{._format}',
    uriVariables: ['id' => new Link(fromClass: OneToOneSubresourceQuestion::class, identifiers: ['id'], fromProperty: 'answer')],
    status: 200,
    operations: [new Get()]
)]
#[ORM\Entity]
class OneToOneSubresourceAnswer
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private ?string $content = null;

    #[ORM\OneToOne(targetEntity: OneToOneSubresourceQuestion::class, mappedBy: 'answer')]
    private ?OneToOneSubresourceQuestion $question = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getQuestion(): ?OneToOneSubresourceQuestion
    {
        return $this->question;
    }

    public function setQuestion(?OneToOneSubresourceQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }
}
