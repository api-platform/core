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
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity]
class OneToOneSubresourceQuestion
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?string $content = null;

    #[ORM\OneToOne(targetEntity: OneToOneSubresourceAnswer::class, inversedBy: 'question', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'answer_id', referencedColumnName: 'id')]
    private ?OneToOneSubresourceAnswer $answer = null;

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

    public function getAnswer(): ?OneToOneSubresourceAnswer
    {
        return $this->answer;
    }

    public function setAnswer(?OneToOneSubresourceAnswer $answer): self
    {
        $this->answer = $answer;

        return $this;
    }
}
