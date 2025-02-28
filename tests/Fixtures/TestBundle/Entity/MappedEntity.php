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

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * MappedEntity to MappedResource.
 */
#[ORM\Entity]
#[Map(target: MappedResource::class)]
class MappedEntity
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    #[Map(if: false)]
    private string $firstName;

    #[Map(target: 'username', transform: [self::class, 'toUsername'])]
    #[ORM\Column]
    private string $lastName;

    public static function toUsername($value, $object): string {
        return $object->getFirstName() . ' ' . $object->getLastName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLastName(string $name): void
    {
        $this->lastName = $name;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setFirstName(string $name): void
    {
        $this->firstName = $name;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }
}
