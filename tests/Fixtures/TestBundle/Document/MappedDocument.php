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

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceOdm;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * MappedEntity to MappedResource.
 */
#[ODM\Document]
#[Map(target: MappedResourceOdm::class)]
class MappedDocument
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;

    #[ODM\Field(type: 'string')]
    #[Map(if: false)]
    private string $firstName;

    #[Map(target: 'username', transform: [self::class, 'toUsername'])]
    #[ODM\Field(type: 'string')]
    private string $lastName;

    public static function toUsername($value, $object): string
    {
        return $object->getFirstName().' '.$object->getLastName();
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
