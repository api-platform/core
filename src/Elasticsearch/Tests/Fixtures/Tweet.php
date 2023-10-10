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

namespace ApiPlatform\Elasticsearch\Tests\Fixtures;

use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['tweet:read']])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'author.id'])]
#[ApiFilter(MatchFilter::class, properties: ['message', 'author.firstName'])]
class Tweet
{
    #[Groups(['tweet:read', 'user:read'])]
    #[ApiProperty(identifier: true)]
    private ?string $id = null;

    #[Groups(['tweet:read'])]
    private ?User $author = null;

    #[Groups(['tweet:read', 'user:read'])]
    private ?\DateTimeImmutable $date = null;

    #[Groups(['tweet:read', 'user:read'])]
    private ?string $message = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): void
    {
        $this->author = $author;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
