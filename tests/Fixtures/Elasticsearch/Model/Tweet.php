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

namespace ApiPlatform\Core\Tests\Fixtures\Elasticsearch\Model;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"tweet:read"}
 *     }
 * )
 * @ApiFilter(OrderFilter::class, properties={"id", "author.id"})
 * @ApiFilter(MatchFilter::class, properties={"message", "author.firstName"})
 */
class Tweet
{
    /**
     * @ApiProperty(identifier=true)
     *
     * @Groups({"tweet:read", "user:read"})
     */
    private $id;

    /**
     * @Groups({"tweet:read"})
     */
    private $author;

    /**
     * @Groups({"tweet:read", "user:read"})
     */
    private $date;

    /**
     * @Groups({"tweet:read", "user:read"})
     */
    private $message;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
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
