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

use ApiPlatform\Elasticsearch\Filter\TermFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['user:read']])]
#[ApiFilter(TermFilter::class, properties: ['id', 'gender', 'age', 'firstName', 'tweets.id', 'tweets.date'])]
class User
{
    #[ApiProperty(identifier: true)]
    #[Groups(['tweet:read', 'user:read'])]
    private ?string $id = null;

    #[Groups(['tweet:read', 'user:read'])]
    private ?string $gender = null;

    #[Groups(['tweet:read', 'user:read'])]
    private ?int $age = null;

    #[Groups(['tweet:read', 'user:read'])]
    private ?string $firstName = null;

    #[Groups(['tweet:read', 'user:read'])]
    private ?string $lastName = null;

    #[Groups(['user:read'])]
    private ?\DateTimeInterface $registeredAt = null;

    #[Groups(['user:read'])]
    private array $tweets = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeInterface $registeredAt): void
    {
        $this->registeredAt = $registeredAt;
    }

    public function getTweets(): array
    {
        return $this->tweets;
    }

    public function setTweets(array $tweets): void
    {
        $this->tweets = $tweets;
    }

    public function addTweet(Tweet $tweet): void
    {
        if (\in_array($tweet, $this->tweets, true)) {
            return;
        }

        $this->tweets[] = $tweet;
    }

    public function removeTweet(Tweet $tweet): void
    {
        $index = array_search($tweet, $this->tweets, true);

        if (!\is_int($index)) {
            return;
        }

        array_splice($this->tweets, $index, 1);
    }
}
