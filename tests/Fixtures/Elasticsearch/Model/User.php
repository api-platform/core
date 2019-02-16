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
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups"={"user:read"}
 *     }
 * )
 * @ApiFilter(TermFilter::class, properties={"id", "gender", "age", "firstName", "tweets.id", "tweets.date"})
 */
class User
{
    /**
     * @ApiProperty(identifier=true)
     *
     * @Groups({"user:read", "tweet:read"})
     */
    private $id;

    /**
     * @Groups({"user:read", "tweet:read"})
     */
    private $gender;

    /**
     * @Groups({"user:read", "tweet:read"})
     */
    private $age;

    /**
     * @Groups({"user:read", "tweet:read"})
     */
    private $firstName;

    /**
     * @Groups({"user:read", "tweet:read"})
     */
    private $lastName;

    /**
     * @Groups({"user:read"})
     */
    private $tweets = [];

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

    public function setAge(int $age)
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
